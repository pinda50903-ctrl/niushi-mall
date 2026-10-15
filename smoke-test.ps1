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
    $json = $body | ConvertTo-Json -Depth 5
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

# 1. 平台登录
$r = Post '/platform/passport/login' @{ username = 'admin'; password = 'Admin@2024' }
Assert ($r.code -eq 0) "平台登录 code=0"
$ptoken = $r.data.token

# 2. 开通租户（重复创建视为通过：幂等冒烟）
$r = Post '/platform/tenant/create' @{ name = '演示商城'; slug = 'demo'; username = 'boss'; password = 'Boss@2024' } $ptoken
Assert ($r.code -eq 0 -or $r.message -match '占用') "开通租户 slug=demo"
$tenantId = 1
if ($r.data.tenant.id) { $tenantId = $r.data.tenant.id }

# 3. 租户管理端登录
$r = Post '/manage/passport/login' @{ username = 'boss'; password = 'Boss@2024' }
Assert ($r.code -eq 0) "商城管理端登录"
$mtoken = $r.data.token

# 4. 建分类+商品
$r = Post '/manage/category/save' @{ name = '数码'; parentId = 0 } $mtoken
Assert ($r.code -eq 0) "创建分类"
$catId = $r.data.category.id
$r = Post '/manage/goods/save' @{
    title = '测试手机'; categoryId = $catId; price = 1999; stock = 100
    cover = 'uploads/demo.jpg'; isSkuEnabled = 0; status = 1
} $mtoken
Assert ($r.code -eq 0) "创建商品"
$goodsId = $r.data.goods.id

# 5. 上传/素材
$r = Get2 '/manage/setting/get?code=all' $mtoken
Assert ($r.code -eq 0) "读取全部设置"

# 6. 用户端: 注册+登录
$r = Post '/api/passport/mobile-login' @{ mobile = '13800138000'; password = 'User@2024' } '' 'demo'
Assert ($r.code -eq 0) "用户端手机号登录"
$utoken = $r.data.token

# 7. 首页
$r = Get2 '/api/home/index' '' 'demo'
Assert ($r.code -eq 0) "用户端首页"
Assert ($r.data.tenant.name -eq '演示商城') "首页返回租户信息"

# 8. 商品列表/详情
$r = Get2 '/api/goods/list' '' 'demo'
Assert ($r.code -eq 0 -and $r.data.total -ge 1) "商品列表"
$r = Get2 "/api/goods/detail?id=$goodsId" '' 'demo'
Assert ($r.code -eq 0) "商品详情"

# 9. 收藏
$r = Post '/api/goods/favorite-toggle' @{ goodsId = $goodsId } $utoken 'demo'
Assert ($r.code -eq 0) "收藏商品"

# 10. 下单
$r = Post '/api/order/submit' @{ goodsId = $goodsId; num = 2; deliveryType = 1 } $utoken 'demo'
Assert ($r.code -eq 0) "提交订单(自提)"
$orderId = $r.data.order.id
$payAmount = $r.data.order.payAmount

# 11. 支付(微信模拟)
$r = Post '/api/pay/prepay' @{ orderId = $orderId; channel = 'wechat' } $utoken 'demo'
Assert ($r.code -eq 0) "预支付"
if ($r.data.mock -eq $true) {
    $r2 = Post '/api/pay/mock' @{ payNo = $r.data.payNo } $utoken 'demo'
    Assert ($r2.code -eq 0) "模拟支付成功"
} else {
    Assert $true "预支付(非mock)"
}

# 12. 订单状态
$r = Get2 "/api/order/detail?id=$orderId" $utoken 'demo'
Assert ($r.data.order.status -eq 20) "支付后进入待发货"

# 13. 管理端发货
$r = Post '/manage/order/deliver' @{ id = $orderId; expressNo = 'SF123456' } $mtoken
Assert ($r.code -eq 0) "商家发货"

# 14. 用户收货
$r = Post '/api/order/receipt' @{ id = $orderId } $utoken 'demo'
Assert ($r.code -eq 0) "确认收货"

# 15. 优惠券全流程
$r = Post '/manage/coupon/save' @{ name = '满100减20'; kind = 1; offAmount = 20; minAmount = 100; stock = 50; perLimit = 1; expireKind = 1; validFrom = '2025-01-01'; validTo = '2030-01-01'; status = 1 } $mtoken
Assert ($r.code -eq 0) "创建优惠券"
$couponId = $r.data.coupon.id
$r = Get2 '/api/coupon/center' $utoken 'demo'
Assert ($r.code -eq 0) "领券中心"
$r = Post '/api/coupon/receive' @{ id = $couponId } $utoken 'demo'
Assert ($r.code -eq 0) "领取优惠券"
$r = Get2 '/api/coupon/mine' $utoken 'demo'
Assert ($r.code -eq 0 -and $r.data.total -ge 1) "我的券包"

# 16. 购物车
$r = Post '/api/cart/add' @{ goodsId = $goodsId; num = 1 } $utoken 'demo'
Assert ($r.code -eq 0) "加入购物车"
$r = Get2 '/api/cart/index' $utoken 'demo'
Assert ($r.code -eq 0 -and $r.data.count -ge 1) "购物车列表"

# 17. 仪表盘
$r = Get2 '/manage/dashboard/index' $mtoken
Assert ($r.code -eq 0) "商城仪表盘"

# 18. 平台总览
$r = Get2 '/platform/overview/index' $ptoken
Assert ($r.code -eq 0) "平台总览"

# 19. 插件市场
$r = Get2 '/platform/plugin/market' $ptoken
Assert ($r.code -eq 0) "插件市场(本地扫描)"

if ($script:failed) { Write-Output '=== 有失败项 ===' } else { Write-Output '=== 全部通过 ===' }
