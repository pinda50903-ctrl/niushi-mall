<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\common\components\ApiException;
use app\common\components\TokenService;
use app\common\model\User;
use app\common\model\UserAuth;
use app\common\services\SettingService;
use app\common\services\TenantContext;
use Yii;

/**
 * 用户登录
 *
 * 渠道：weapp（微信小程序 code2session）/ h5（手机号+密码或游客）
 */
final class PassportController extends UserAwareController
{
    /** 小程序登录：jsCode → openid → 自动注册 */
    public function actionWeappLogin(): \yii\web\Response
    {
        $code = $this->strInput('code');
        $nickname = $this->strInput('nickname');
        $avatar = $this->strInput('avatar');
        if ($code === '') {
            $this->fail('缺少登录凭证');
        }
        $tenantId = $this->tenantId();

        [$openid, $unionid] = $this->code2session($code, $tenantId);
        $user = $this->ensureUser($tenantId, 'weapp', $openid, $unionid, $nickname, $avatar);
        $token = TokenService::issue('user', (int)$user->id, $tenantId, 86400 * 30);
        return $this->ok($this->loginPayload($user, $token));
    }

    /** H5 手机号密码登录（自动注册） */
    public function actionMobileLogin(): \yii\web\Response
    {
        $mobile = $this->strInput('mobile');
        $password = (string)$this->input('password', '');
        if (!preg_match('/^1\d{10}$/', $mobile)) {
            $this->fail('手机号格式不正确');
        }
        if (strlen($password) < 6) {
            $this->fail('密码至少 6 位');
        }
        $tenantId = $this->tenantId();
        /** @var User|null $user */
        $user = User::find()
            ->where(['tenant_id' => $tenantId, 'mobile' => $mobile])
            ->one();
        if (!$user) {
            $user = new User();
            $user->tenant_id = $tenantId;
            $user->mobile = $mobile;
            $user->nickname = '用户' . substr($mobile, -4);
            $user->status = 1;
            $user->save(false);
        } elseif ((int)$user->status !== 1) {
            $this->fail('账号已被禁用');
        } elseif ($user->password_hash === '') {
            $user->password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
            $user->save(false);
        } elseif (!Yii::$app->getSecurity()->validatePassword($password, (string)$user->password_hash)) {
            $this->fail('密码不正确');
        }
        $token = TokenService::issue('user', (int)$user->id, $tenantId, 86400 * 30);
        return $this->ok($this->loginPayload($user, $token));
    }

    /** 游客 token（可浏览，下单需升级登录） */
    public function actionGuest(): \yii\web\Response
    {
        $tenantId = $this->tenantId();
        $token = TokenService::issue('user', 0, $tenantId, 86400);
        return $this->ok(['token' => $token, 'guest' => true]);
    }

    public function actionProfile(): \yii\web\Response
    {
        $user = $this->currentUser();
        if (!$user || (int)$user->id === 0) {
            $this->fail('请先登录', ApiException::UNAUTHORIZED);
        }
        return $this->ok(['profile' => $this->loginPayload($user)['profile']]);
    }

    public function actionUpdateProfile(): \yii\web\Response
    {
        $user = $this->currentUser();
        if (!$user || (int)$user->id === 0) {
            $this->fail('请先登录', ApiException::UNAUTHORIZED);
        }
        if ($this->input('nickname') !== null) {
            $user->nickname = $this->strInput('nickname');
        }
        if ($this->input('avatar') !== null) {
            $user->avatar = $this->strInput('avatar');
        }
        $user->save(false);
        return $this->ok(['profile' => $this->loginPayload($user)['profile']], '资料已更新');
    }

    /** code2session（未配置公众号时返回模拟 openid 便于联调） */
    private function code2session(string $code, int $tenantId): array
    {
        $conf = SettingService::get('wechat', $tenantId);
        if ($conf['app_id'] === '' || $conf['app_secret'] === '') {
            // 未配置：以 code 生成稳定 openid，联调闭环
            return ['mock_' . md5($code . $tenantId), ''];
        }
        $client = new \yii\httpclient\Client();
        $resp = $client->createRequest()
            ->setUrl('https://api.weixin.qq.com/sns/jscode2session')
            ->setData([
                'appid' => $conf['app_id'],
                'secret' => $conf['app_secret'],
                'js_code' => $code,
                'grant_type' => 'authorization_code',
            ])
            ->setFormat(\yii\httpclient\Client::FORMAT_JSON)
            ->send();
        $data = $resp->getData() ?: [];
        if (empty($data['openid'])) {
            throw new ApiException('微信登录失败：' . ($data['errmsg'] ?? '未知错误'), ApiException::VALIDATE);
        }
        return [$data['openid'], (string)($data['unionid'] ?? '')];
    }

    private function ensureUser(int $tenantId, string $channel, string $openid, string $unionid, string $nickname = '', string $avatar = ''): User
    {
        /** @var UserAuth|null $auth */
        $auth = UserAuth::find()
            ->where(['tenant_id' => $tenantId, 'channel' => $channel, 'openid' => $openid])
            ->one();
        if ($auth) {
            /** @var User|null $user */
            $user = User::find()->where(['id' => $auth->user_id])->one();
            if ($user && (int)$user->status === 1) {
                if ($nickname !== '' || $avatar !== '') {
                    if ($nickname !== '') {
                        $user->nickname = $nickname;
                    }
                    if ($avatar !== '') {
                        $user->avatar = $avatar;
                    }
                    $user->save(false);
                }
                return $user;
            }
            $this->fail('账号不可用');
        }
        $tx = Yii::$app->db->beginTransaction();
        try {
            $user = new User();
            $user->tenant_id = $tenantId;
            $user->nickname = $nickname !== '' ? $nickname : '用户' . substr($openid, -6);
            $user->avatar = $avatar;
            $user->status = 1;
            $user->save(false);

            $auth = new UserAuth();
            $auth->tenant_id = $tenantId;
            $auth->user_id = (int)$user->id;
            $auth->channel = $channel;
            $auth->openid = $openid;
            $auth->unionid = $unionid;
            $auth->save(false);
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
        return $user;
    }

    private function loginPayload(User $user, string $token = ''): array
    {
        return [
            'token' => $token,
            'profile' => [
                'id' => (int)$user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'mobile' => $user->mobile,
                'balance' => (float)$user->balance,
                'integral' => (int)$user->integral,
                'level' => (int)$user->level,
                'isGuest' => (int)$user->id === 0,
            ],
        ];
    }
}
