$ErrorActionPreference = 'Stop'
$base = 'http://127.0.0.1:8090'

function ReadJson($resp) {
    $bytes = $resp.RawContentStream.ToArray()
    $text = [System.Text.Encoding]::UTF8.GetString($bytes)
    return $text | ConvertFrom-Json
}
function Post($url, $body, $token = '', $mallId = '') {
    $headers = @{}
    if ($token) { $headers['Authorization'] = "Bearer $token" }
    if ($mallId) { $headers['X-Mall-Id'] = $mallId }
    $json = $body | ConvertTo-Json -Depth 6
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($json)
    $resp = Invoke-WebRequest -Uri "$base$url" -Method POST -Body $bytes -ContentType 'application/json; charset=utf-8' -Headers $headers -UseBasicParsing -TimeoutSec 10
    return ReadJson $resp
}
function Get2($url, $token = '', $mallId = '') {
    $headers = @{}
    if ($token) { $headers['Authorization'] = "Bearer $token" }
    if ($mallId) { $headers['X-Mall-Id'] = $mallId }
    $resp = Invoke-WebRequest -Uri "$base$url" -Method GET -Headers $headers -UseBasicParsing -TimeoutSec 10
    return ReadJson $resp
}
function Assert($cond, $name) {
    if ($cond) { Write-Output "PASS  $name" } else { Write-Output "FAIL  $name"; $script:failed = $true }
}
$script:failed = $false

# ===== 1. 平台：上架 4 个插件并授权给租户 =====
$r = Post '/platform/passport/login' @{ username = 'admin'; password = 'Admin@2024' }
Assert ($r.code -eq 0) '平台登录'
$ptoken = $r.data.token

foreach ($code in @('groupon', 'seckill', 'distribution', 'check_in')) {
    $r = Post '/platform/plugin/toggle' @{ code = $code; status = 1 } $ptoken
    Assert ($r.code -eq 0) "插件上架 $code"
    $r = Post '/platform/plugin/grant' @{ code = $code; tenantId = 1 } $ptoken
    Assert ($r.code -eq 0) "租户授权 $code"
}

# ===== 2. 商城管理端 =====
$r = Post '/manage/passport/login' @{ username = 'boss'; password = 'Boss@2024' }
Assert ($r.code -eq 0) '商城管理端登录'
$mtoken = $r.data.token

# ===== 3. 插件配置读写 =====
$r = Get2 '/distribution/admin/config' $mtoken
Assert ($r.code -eq 0 -and $r.data.config.first_ratio -gt 0) '分销配置读取'
$r = Post '/distribution/admin/save-config' @{ config = @{ first_ratio = 10; second_ratio = 5; min_withdraw = 10 } } $mtoken
Assert ($r.code -eq 0) '分销配置保存'
$r = Post '/check_in/admin/save-config' @{ config = @{ base_integral = 5; seven_bonus = 10; thirty_bonus = 50; makeup_cost = 20 } } $mtoken
Assert ($r.code -eq 0) '签到配置保存'

# ===== 4. 测试商品 =====
$r = Post '/manage/category/save' @{ name = '插件测试'; parentId = 0 } $mtoken
$catId = if ($r.data.category.id) { $r.data.category.id } else { 1 }
$r = Post '/manage/goods/save' @{
    title = '插件测试机'; categoryId = $catId; price = 599; stock = 50
    cover = 'uploads/demo.jpg'; isSkuEnabled = 0; status = 1
} $mtoken
Assert ($r.code -eq 0) '创建测试商品'
$gid = $r.data.goods.id

# ===== 5. 秒杀：建场次 + 用户端场次列表 =====
$r = Post '/seckill/admin/save-session' @{
    name = '晚间秒杀'; startAt = 'now'; endAt = '+1 day'
    goods = @(@{ goodsId = $gid; seckillPrice = 399; stock = 10; perLimit = 1 })
} $mtoken
Assert ($r.code -eq 0) '创建秒杀场次'
$r = Get2 '/seckill/session/index' '' 'demo'
Assert ($r.code -eq 0 -and $r.data.list.Count -ge 1) '秒杀场次列表'
Assert ($r.data.list[0].goods[0].seckillPrice -eq 399) '秒杀价透出'

# ===== 6. 拼团：建活动 + 开团 + 支付 =====
$r = Post '/groupon/admin/save-activity' @{
    goodsId = $gid; title = '三人拼团'; groupPrice = 299; groupSize = 3
    timeLimitHours = 24; stock = 20; endAt = '+30 days'
} $mtoken
Assert ($r.code -eq 0) '创建拼团活动'
$activityId = $r.data.activityId

$r = Post '/api/passport/mobile-login' @{ mobile = '13900139000'; password = 'User@2024' } '' 'demo'
Assert ($r.code -eq 0) '用户B登录'
$btoken = $r.data.token

$r = Post '/groupon/team/launch' @{ activityId = $activityId; deliveryType = 1 } $btoken 'demo'
Assert ($r.code -eq 0) '用户B开团'
$teamId = $r.data.teamId
$grouponOrderId = $r.data.orderId
$r = Post '/api/pay/prepay' @{ orderId = $grouponOrderId; channel = 'wechat' } $btoken 'demo'
if ($r.data.mock -eq $true) {
    $r2 = Post '/api/pay/mock' @{ payNo = $r.data.payNo } $btoken 'demo'
    Assert ($r2.code -eq 0) '拼团订单模拟支付'
}
$r = Get2 "/groupon/team/view?teamId=$teamId" $btoken 'demo'
Assert ($r.code -eq 0 -and $r.data.members.Count -eq 1) '团详情与成员'

# ===== 7. 分销全链路：绑定 → 推广下单 → 佣金结算 → 提现 =====
$r = Post '/api/passport/mobile-login' @{ mobile = '13800138000'; password = 'User@2024' } '' 'demo'
$atoken = $r.data.token
$r = Get2 '/distribution/center/index' $atoken 'demo'
Assert ($r.code -eq 0 -and $r.data.inviteCode -ne '') '分销中心与邀请码'
$inviteCode = $r.data.inviteCode

$r = Post '/distribution/center/bind' @{ inviteCode = $inviteCode } $btoken 'demo'
Assert ($r.code -eq 0 -or $r.message -match '已绑定') '用户B绑定上级A(或已存在)'
$r = Post '/distribution/center/bind' @{ inviteCode = $inviteCode } $btoken 'demo'
Assert ($r.code -ne 0) '重复绑定被拒绝'

$r = Post '/api/order/submit' @{ goodsId = $gid; num = 1; deliveryType = 1 } $btoken 'demo'
Assert ($r.code -eq 0) '用户B下单(被推广)'
$order2 = $r.data.order.id
$r = Post '/api/pay/prepay' @{ orderId = $order2; channel = 'wechat' } $btoken 'demo'
if ($r.data.mock -eq $true) {
    Post '/api/pay/mock' @{ payNo = $r.data.payNo } $btoken 'demo' | Out-Null
}
$r = Get2 '/distribution/center/commissions?status=0' $atoken 'demo'
Assert ($r.code -eq 0 -and $r.data.total -ge 1) '支付后产生待结算佣金'

Post '/manage/order/deliver' @{ id = $order2; expressNo = 'SF888888' } $mtoken | Out-Null
$r = Post '/api/order/receipt' @{ id = $order2 } $btoken 'demo'
Assert ($r.code -eq 0) '用户B确认收货'

$r = Get2 '/distribution/center/commissions?status=1' $atoken 'demo'
Assert ($r.code -eq 0 -and $r.data.total -ge 1) '收货后佣金入账'
$r = Get2 '/distribution/center/index' $atoken 'demo'
Assert ($r.data.settledAmount -ge 59.9) "佣金金额正确(59.9): $($r.data.settledAmount)"
Assert ($r.data.teamLevel1 -ge 1) '团队一级人数'

# 业务规则：一时间存在待审核提现时不允许再申请，先把历史待审核单驳回以保证脚本可重复执行
$r = Get2 '/distribution/center/withdraw-logs' $atoken 'demo'
foreach ($w in $r.data.list) {
    if ($w.status -eq 0) {
        Post '/distribution/admin/audit-withdraw' @{ id = $w.id; agree = 0; remark = '冒烟重置' } $mtoken | Out-Null
    }
}
$r = Post '/distribution/center/withdraw-apply' @{ amount = 20 } $atoken 'demo'
Assert ($r.code -eq 0) '申请提现'
$withdrawId = $r.data.withdrawId
$r = Post '/distribution/admin/audit-withdraw' @{ id = $withdrawId; agree = 1 } $mtoken
Assert ($r.code -eq 0) '审核打款'
$r = Get2 '/distribution/center/withdraw-logs' $atoken 'demo'
Assert ($r.data.list[0].statusText -eq '已打款') '提现记录已打款'
$r = Get2 '/distribution/center/team?level=1' $atoken 'demo'
Assert ($r.code -eq 0 -and $r.data.total -ge 1) '我的团队列表'

# ===== 8. 签到全流程 =====
$r = Get2 '/check_in/check/status' $atoken 'demo'
$todaySigned = $r.data.todaySigned
Assert ($r.code -eq 0) '签到状态'
if (-not $todaySigned) {
    $r = Post '/check_in/check/do' @{} $atoken 'demo'
    Assert ($r.code -eq 0 -and $r.data.rewardIntegral -ge 5) '今日签到得积分'
}
$r = Get2 '/check_in/check/status' $atoken 'demo'
Assert ($r.data.todaySigned -eq $true) '签到后状态更新'
Assert ($r.data.monthDays.Count -ge 1) '月度日历有记录'

# 补签：先充值积分，再从当月日历里挑一个「本月、已过、尚无记录」的日期，保证脚本可重复执行
$r = Get2 '/manage/user/index?keyword=13800138000' $mtoken
$aUserId = $r.data.list[0].id
Post '/manage/user/adjust-integral' @{ id = $aUserId; amount = 100 } $mtoken | Out-Null
$r = Get2 '/check_in/check/status' $atoken 'demo'
$month = [int]$r.data.month
$today = [int]((Get-Date).ToString('yyyyMMdd'))
$existing = @{}
foreach ($d in $r.data.monthDays) { $existing[[int]$d.ymd] = $true }
# 当月已过的日期，从新到旧找第一个无记录的
$candidate = 0
$lastDay = (Get-Date).Day
for ($d = $lastDay - 1; $d -ge 1; $d--) {
    $ymd = [int]("$month" + $d.ToString('00'))
    if ($ymd -lt $today -and -not $existing.ContainsKey($ymd)) { $candidate = $ymd; break }
}
Assert ($candidate -gt 0) '找到可补签日期'
if ($candidate -gt 0) {
    $r = Post '/check_in/check/makeup' @{ ymd = $candidate } $atoken 'demo'
    Assert ($r.code -eq 0) '补签成功扣积分'
    $r = Post '/check_in/check/makeup' @{ ymd = $candidate } $atoken 'demo'
    Assert ($r.code -ne 0) '重复补签被拒绝'
}

$r = Get2 '/check_in/admin/records' $mtoken
Assert ($r.code -eq 0 -and $r.data.total -ge 1) '管理端打卡记录'

if ($script:failed) { Write-Output '=== 插件冒烟：有失败项 ===' } else { Write-Output '=== 插件冒烟：全部通过 ===' }
