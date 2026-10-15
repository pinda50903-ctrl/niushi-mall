<?php

declare(strict_types=1);

namespace app\common\components;

use yii\data\Pagination;
use yii\db\ActiveQuery;
use yii\rest\Controller;
use yii\web\Response;

/**
 * REST 控制器基类：统一响应包封、请求读取、分页
 */
abstract class RestController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * 成功响应
     */
    protected function ok(mixed $data = null, string $message = 'ok'): Response
    {
        \Yii::$app->response->data = ['code' => 0, 'message' => $message, 'data' => $data];
        return \Yii::$app->response;
    }

    /**
     * 抛出业务错误
     */
    protected function fail(string $message, int $code = ApiException::VALIDATE): never
    {
        throw new ApiException($message, $code);
    }

    /**
     * 读取请求体（JSON 或表单）
     */
    protected function body(): array
    {
        $body = \Yii::$app->request->post();
        if ($body === []) {
            $raw = \Yii::$app->request->getRawBody();
            $json = json_decode((string)$raw, true);
            if (is_array($json)) {
                $body = $json;
            }
        }
        return $body;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        $body = $this->body();
        if (array_key_exists($key, $body)) {
            return $body[$key];
        }
        $value = \Yii::$app->request->get($key);
        return $value !== null ? $value : $default;
    }

    protected function intInput(string $key, int $default = 0): int
    {
        return (int)$this->input($key, $default);
    }

    protected function strInput(string $key, string $default = ''): string
    {
        return trim((string)$this->input($key, $default));
    }

    /**
     * 分页参数（page/pageSize，pageSize 上限 100）
     * @return array{0: int, 1: int}
     */
    protected function pageRange(): array
    {
        $page = max(1, $this->intInput('page', 1));
        $size = min(100, max(1, $this->intInput('pageSize', 20)));
        return [$page, $size];
    }

    /**
     * 为查询应用分页并返回分页对象
     */
    protected function paginate(ActiveQuery $query): Pagination
    {
        [$page, $size] = $this->pageRange();
        $pagination = new Pagination();
        $pagination->totalCount = (int)(clone $query)->count();
        $pagination->page = $page - 1;
        $pagination->pageSize = $size;
        $query->offset($pagination->offset)->limit($pagination->limit);
        return $pagination;
    }

    /**
     * 标准列表响应
     */
    protected function okList(ActiveQuery $query, callable $formatter = null): Response
    {
        $pagination = $this->paginate(clone $query);
        $rows = $query->all();
        if ($formatter) {
            $rows = array_map($formatter, $rows);
        }
        return $this->ok([
            'list' => $rows,
            'total' => $pagination->totalCount,
            'page' => $pagination->page + 1,
            'pageSize' => $pagination->pageSize,
        ]);
    }
}
