<?php
namespace app\components;

// DOCUMENT
// https://mahakacc.mahaksoft.com/API/v3/swagger/index.html

use app\models\CompanyStock;
use Yii;
use yii\base\component;
use yii\base\Exception;
use app\models\Products;
use app\models\Details;
use app\models\Factors;
use app\models\Orders;
use app\models\User;
use SoapClient;

require_once(Yii::$app->basePath . "/components/lib/nusoap.php");

class Mahak extends component
{
    public $userName = "3529655";
    public $passWord = "851957";
    public $dataBaseID = 2795875;

    public $People;
    public $Products;
    public $ProductDetails;
    public $ProductCounts;

    /*public function login()
    {
        $arrContextOptions=array(
            "ssl"=>array(
                'verify_peer' => false,
                'verify_peer_name' => false
            ),
        );  

        $loginData = json_decode(file_get_contents("https://bazaraservices.mahaksoft.com/sync/login?username=1002337@gmail.com&password=412315", FALSE, stream_context_create($arrContextOptions)), TRUE);
        // die(var_dump($loginData));
        return $loginData["data"]["token"];
    }*/

    public function newlogin()
    {
        $data = array(
            "userName" => '2501292',
            "password" => "809374",
            "databaseId" => 2797887,
            "packageNo" => "2501292",
            "language" => "fa",
            "description" => "Test Api"
        );

        $url = "https://mahakacc.mahaksoft.com/API/v3/Sync/Login";
        $options = array(
            'http' => array(
                'method' => 'POST',
                'content' => json_encode($data),
                'header' => "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n"
            ),
            "ssl" => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            ),
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $response = json_decode($result);
        // return $response;
        return $response->Data->UserToken;
    }

    public function GetAllData($auto = 0)
    {
        // چک کردن اخرین تایم اپدیت
        $dit = Details::findOne(1);
        // if(($dit->last_update + 900) > time() && $auto == 0)
        // {
        //     return true;
        // }
        // else
        // {
        //     $dit->last_update = strval(time());
        //     $dit->save();
        // }


        $token = $this->newlogin();
        ;

        $data = array(
            // "fromBankVersion"=> 0,
            // "fromRegionVersion"=> 0,
            // "fromChecklistVersion"=> 0,
            // "fromChequeVersion"=> 0,
            // "fromExtraDataVersion"=> 0,
            // "fromOrderDetailVersion"=> 0,
            // "fromOrderVersion"=> 0,

            "fromPersonGroupVersion" => 0,
            "fromPersonVersion" => 0,
            "fromPersonAddressVersion" => 0,

            // "fromPictureVersion"=> 0,
            // "fromProductCategoryVersion"=> 0,

            "fromProductDetailVersion" => 0,
            "fromProductVersion" => 0,

            "fromReceiptVersion" => 0,
            // "fromSettingVersion"=> 0,
            // "fromTransactionVersion"=> 0,
            "fromVisitorVersion" => 0,
            "fromVisitorPersonVersion" => 0,
            "fromVisitorProductVersion" => 0,
            // "fromCostLevelNameVersion"=> 0,
            // "fromNotRegisterVersion"=> 0,
            // "fromPromotionVersion"=> 0,
            // "fromPromotionDetailVersion"=> 0,
            // "fromPromotionEntityVersion"=> 0,
            // "fromReturnReasonVersion"=> 0,
            // "fromTransferAccountVersion"=> 0,
            // "fromTransferStoreVersion"=> 0,
            // "fromTransferStoreDetailVersion"=> 0,
            // "fromVisitorLocationVersion"=> 0,
            // "fromProductPropertyVersion"=> 0,
            // "fromPropertyDescriptionVersion"=> 0,
            // "fromIncomeVersion"=> 0,
            // "fromIncomeGroupVersion"=> 0,
            // "fromProjectVersion"=> 0,
            // "fromStoreVersion"=> 0,
            // "fromPhotoGalleryVersion"=> 0,
            "fromProductDetailStoreAssetVersion" => 0,
            // "orderTypes"=> [
            //     0
            // ]
        );

        // return $token;

        $url = "https://mahakacc.mahaksoft.com/API/v3/Sync/GetAllData";
        $options = array(
            'http' => array(
                'method' => 'POST',
                'content' => json_encode($data),
                'header' => "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Authorization: Bearer " . $token
            ),
            "ssl" => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            ),
        );

        /*$curl_handle=curl_init();
        curl_setopt($curl_handle, CURLOPT_URL,'https://mahakacc.mahaksoft.com/API/v3/Sync/GetAllData');
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Your application name');
        $query = curl_exec($curl_handle);
        curl_close($curl_handle);

        return $query;*/


        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $response = json_decode($result);

        // die(var_dump($response->Data->Objects));

        $this->People = $response->Data->Objects->People;

        $this->Products = $response->Data->Objects->Products;
        $this->ProductDetails = $response->Data->Objects->ProductDetails;
        $this->ProductCounts = $response->Data->Objects->ProductDetailStoreAssets;


        // $this->setPeaple($this->People);
        $this->setProducts($this->Products);
        $this->setPrice($this->ProductDetails);
        $this->setCounts($this->ProductCounts);

        return $response;
    }

    // بروزرسانی کاربران از محک
    public function setPeaple($peaple)
    {
        /*echo "<pre>";
        die(var_dump($peaple));*/
        if ($peaple) {
            foreach ($peaple as $key => $value) {
                // echo $value->PersonCode ." - ". $value->PersonId . "<br>";
                $user = User::find()->where(['personcode' => $value->PersonCode])->one();
                if ($user != NULL && $user->personid != $value->PersonId) {
                    $user->personid = intval($value->PersonId);
                    if (!$user->save()) {
                        var_dump($user->id);
                        echo "<pre>";
                        die(var_dump($user->errors));
                    }
                }
            }
        }
        return true;
    }

    // بروزرسانی کد کالاها از محک
    public function setProducts($products)
    {
        if ($products) {
            foreach ($products as $key => $value) {
                $product = Products::find()->where(["code" => $value->ProductCode])->one();
                $product_stock = CompanyStock::find()->where(["code" => $value->ProductCode])->one();

                if ($product != NULL && $product->productid != $value->ProductId) {
                    $product->productid = intval($value->ProductId);
                    if (!$product->save()) {
                        var_dump($product->id);
                        echo "<pre>";
                        die(var_dump($product->errors));
                    }
                }

                if ($product_stock != NULL && $product_stock->productid != $value->ProductId) {
                    $product_stock->productid = intval($value->ProductId);
                    if (!$product_stock->save()) {
                        var_dump($product_stock->id);
                        echo "<pre>";
                        die(var_dump($product_stock->errors));
                    }
                }
            }
        }
        return true;
    }

    // بروزرسانی قیمت ها از محک
    public function setPrice($ProductDetails)
    {
        if (!$ProductDetails) {
            return true;
        }

        // مرحله ۱: جمع‌آوری داده‌های ورودی
        $priceMap = [];
        $productIds = [];

        foreach ($ProductDetails as $item) {
            $priceId = $item->ProductDetailId;
            $price = (string) ($item->Price4 / 10);

            $priceMap[$priceId] = [
                'productId' => $item->ProductId,
                'price' => $price,
            ];

            $productIds[] = $item->ProductId;
        }

        // مرحله ۲: دریافت همه محصولات یکجا
        $productIds = array_unique($productIds);

        $products = Products::find()
            ->where(['productid' => $productIds])
            ->indexBy('productid')
            ->all();

        $companyStocks = CompanyStock::find()
            ->where(['productid' => $productIds])
            ->indexBy('productid')
            ->all();

        // مرحله ۳: بروزرسانی
        foreach ($priceMap as $priceId => $data) {
            $productId = $data['productId'];
            $apiPrice = $data['price']; // قیمت از API (ممکنه "0" باشه)

            // اگر محصول در دیتابیس وجود داره
            if (isset($products[$productId])) {
                $p = $products[$productId];

                // اگر قیمت API صفر نیست → استفاده کن
                if ($apiPrice != '0') {
                    if ($p->price !== $apiPrice || $p->productpriceid != $priceId) {
                        $p->price = $apiPrice;
                        $p->productpriceid = $priceId;
                        $p->save(false);
                    }
                }
                // اگر قیمت API صفر بود → از CompanyStock بگیر (اگر صفر نباشه)
                elseif (isset($companyStocks[$productId]) && $companyStocks[$productId]->price != '0') {
                    $stockPrice = $companyStocks[$productId]->price;
                    if ($p->price !== $stockPrice) {
                        $p->price = $stockPrice;
                        $p->save(false);
                    }
                }
            }

            // بروزرسانی CompanyStock
            if (isset($companyStocks[$productId])) {
                $s = $companyStocks[$productId];

                if ($apiPrice != '0') {
                    if ($s->price !== $apiPrice || $s->productpriceid != $priceId) {
                        $s->price = $apiPrice;
                        $s->productpriceid = $priceId;
                        $s->save(false);
                    }
                }
                // اگر API صفر بود، CompanyStock رو دست نزن (قیمت قبلی بمونه)
            }
        }

        return true;
    }
    // بروزرسانی موجودی کالاها از محک
    public function setCounts($ProductCounts)
    {
        $temp = $ProductCounts;
        if ($ProductCounts) {
            foreach ($ProductCounts as $key => $value) {
                $AvailableCount = 0;
                $PriceId = $value->ProductDetailId;
                $product = Products::find()->where(["productpriceid" => $PriceId])->one();
                $product_stock = CompanyStock::find()->where(["productpriceid" => $PriceId])->one();

                foreach ($temp as $key2 => $value2) {
                    if ($value2->ProductDetailId == $PriceId) {
                        $AvailableCount += $value2->Count1;
                    }
                }

                if ($product != NULL) {
                    $product->count = $AvailableCount;
                    if ($AvailableCount > 0) {
                        $product->stock = 1;
                    } else {
                        $product->stock = 0;
                    }
                    if (!$product->save()) {
                        var_dump($product->id);
                        echo "<pre>";
                        die(var_dump($product->errors));
                    }
                }

                if ($product_stock != NULL) {
                    $product_stock->count = $AvailableCount;

                    if (!$product_stock->save()) {
                        var_dump($product_stock->id);
                        echo "<pre>";
                        die(var_dump($product_stock->errors));
                    }
                }

            }
        }
        return true;
    }


    // ثبت فاکتور در بازارا 3
    public function setFactor($item)
    {

        $token = $this->newlogin();
        // return $token;

        $factor = Factors::findOne($item);
        $orders = Orders::find()->where(['factor_id' => $item])->all();
        $myOrders = [];

        if ($orders) {
            foreach ($orders as $key => $value) {
                $product = Products::findOne($value->product_id);
                $temp = [
                    "orderDetailClientId" => strval($value->id),
                    "itemType" => 1,
                    "productDetailId" => strval($product->productpriceid),
                    "price" => strval($value->price),
                    "count1" => strval($value->count),
                    "promotionCode" => 0,
                    "description" => strval($product->name),
                    "discount" => 0,
                    "discountType" => 0,
                    "taxPercent" => 0,
                    "chargePercent" => 0,
                    "storeId" => 2636,
                    "orderClientId" => strval($factor->id),
                    "productDetailClientId" => strval($product->id),
                    "productDetailCode" => strval($product->code)
                ];
                array_push($myOrders, $temp);
            }
        }

        $data = array(
            "orders" => array(
                array(
                    // "orderId"=> 0,
                    "orderClientId" => strval($factor->id),
                    // "orderCode"=> 0,
                    "personId" => strval(Yii::$app->user->identity->id),
                    "visitorId" => 12933,
                    // "receiptId"=> 0,
                    "orderType" => 201,
                    "orderDate" => strval(date("Y-m-d H:i:s")),
                    "deliveryDate" => strval(date("Y-m-d H:i:s")),
                    // "discount"=> 0,
                    // "discountType"=> 0,
                    // "sendCost"=> 0,
                    // "otherCost"=> 0,
                    // "settlementType"=> 0,
                    // "immediate"=> true,
                    // "returnReasonId"=> 0,
                    // "description"=> "string",
                    // "expenseId"=> 0,
                    // "projectId"=> 0,
                    // "latitude"=> 0,
                    // "longitude"=> 0,
                    // "shippingAddress"=> "string",
                    // "deleted"=> true,
                    // "rowVersion"=> 0,
                    // "personClientId"=> 0,
                    "personCode" => strval(Yii::$app->user->identity->mcode),
                    // "receiptClientId"=> 0,
                    // "receiptCode"=> 0
                )
            ),
            "orderDetails" => $myOrders,
            /*"orderDetails"=>array(
                array(
                    // "orderDetailId"=> 0,
                    "orderDetailClientId"=> 360,
                    "itemType"=> 1,
                    // "orderId"=> 1,
                    "productDetailId"=> 1699981,
                    // "incomeId"=> 0,
                    "price"=> 164000,
                    "count1"=> 12,
                    // "count2"=> 0,
                    "promotionCode"=> 0,
                    // "gift"=> 0,
                    "description"=> "توضیحات من هست",
                    "discount"=> 0,
                    "discountType"=> 0,
                    "taxPercent"=> 0,
                    "chargePercent"=> 0,
                    "storeId"=> 2636,
                    // "deleted"=> true,
                    // "rowVersion"=> 0,
                    "orderClientId"=> 260,
                    // "orderCode"=> 0,
                    "productDetailClientId"=> 1010,
                    "productDetailCode"=> 362
                )  
            )*/
        );

        // return json_encode( $data );

        $url = "https://mahakacc.mahaksoft.com/API/v3/Sync/SaveAllData";
        $options = array(
            'http' => array(
                'method' => 'POST',
                'content' => json_encode($data),
                'header' => "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Authorization: Bearer " . $token
            ),
            "ssl" => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            ),
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $response = json_decode($result);


        $myData = $response->Data->Objects->Orders;
        if ($myData->Results[0]->Result) {
            $message_bazara = "OK - Index:" . $myData->Results[0]->Index . " - EntityId:" . $myData->Results[0]->EntityId . " - RowVersion:" . $myData->Results[0]->RowVersion;
        } else {
            $message_bazara = "ERROR - Index:" . $myData->Results[0]->Index . " - Property:" . $myData->Results[0]->Errors[0]->Property . " - Message:" . $myData->Results[0]->Errors[0]->Error;
        }

        // $factor->bazara_message = $message_bazara;
        $factor->save();

        return $message_bazara;
    }

    // ثبت فاکتور در بازارا 3 توسط اپلیکیشن
    public function factorsApp($item)
    {
        $token = $this->newlogin();
        // return $token;

        $factor = Factors::findOne($item);
        $orders = Orders::find()->where(['factor_id' => $item])->all();
        $user = User::findOne($factor->user_id);
        $myOrders = [];
        if ($orders) {
            foreach ($orders as $key => $value) {
                $product = Products::findOne($value->product_id);
                $temp = [
                    "orderDetailClientId" => strval($value->id),
                    "itemType" => 1,
                    "productDetailId" => strval($product->productpriceid),
                    "price" => strval($value->price),
                    "count1" => strval($value->count),
                    "promotionCode" => 0,
                    "description" => strval($product->name),
                    "discount" => 0,
                    "discountType" => 0,
                    "taxPercent" => 0,
                    "chargePercent" => 0,
                    "storeId" => 2636,
                    "orderClientId" => strval($factor->id),
                    "productDetailClientId" => strval($product->id),
                    "productDetailCode" => strval($product->code)
                ];
                array_push($myOrders, $temp);
            }
        }

        $data = array(
            "orders" => array(
                array(
                    // "orderId"=> 0,
                    "orderClientId" => strval($factor->id),
                    // "orderCode"=> 0,
                    "personId" => strval($user->personid),
                    "visitorId" => 12933,
                    // "receiptId"=> 0,
                    "orderType" => 201,
                    "orderDate" => strval(date("Y-m-d H:i:s")),
                    "deliveryDate" => strval(date("Y-m-d H:i:s")),
                    // "discount"=> 0,
                    // "discountType"=> 0,
                    // "sendCost"=> 0,
                    // "otherCost"=> 0,
                    // "settlementType"=> 0,
                    // "immediate"=> true,
                    // "returnReasonId"=> 0,
                    // "description"=> "string",
                    // "expenseId"=> 0,
                    // "projectId"=> 0,
                    // "latitude"=> 0,
                    // "longitude"=> 0,
                    // "shippingAddress"=> "string",
                    // "deleted"=> true,
                    // "rowVersion"=> 0,
                    // "personClientId"=> 0,
                    "personCode" => strval($user->personcode),
                    // "receiptClientId"=> 0,
                    // "receiptCode"=> 0
                )
            ),
            "orderDetails" => $myOrders,
            /*"orderDetails"=>array(
                array(
                    // "orderDetailId"=> 0,
                    "orderDetailClientId"=> 360,
                    "itemType"=> 1,
                    // "orderId"=> 1,
                    "productDetailId"=> 1699981,
                    // "incomeId"=> 0,
                    "price"=> 164000,
                    "count1"=> 12,
                    // "count2"=> 0,
                    "promotionCode"=> 0,
                    // "gift"=> 0,
                    "description"=> "توضیحات من هست",
                    "discount"=> 0,
                    "discountType"=> 0,
                    "taxPercent"=> 0,
                    "chargePercent"=> 0,
                    "storeId"=> 2636,
                    // "deleted"=> true,
                    // "rowVersion"=> 0,
                    "orderClientId"=> 260,
                    // "orderCode"=> 0,
                    "productDetailClientId"=> 1010,
                    "productDetailCode"=> 362
                )  
            )*/
        );

        // return json_encode( $data );

        $url = "https://mahakacc.mahaksoft.com/API/v3/Sync/SaveAllData";
        $options = array(
            'http' => array(
                'method' => 'POST',
                'content' => json_encode($data),
                'header' => "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Authorization: Bearer " . $token
            ),
            "ssl" => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            ),
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $response = json_decode($result);


        return $response->Data->Objects->Orders;
    }

    // برای تطابق دادن کد های کالا در نرم افزار حسابداری با کد های کالا در دیتابیس واسط
    public function products()
    {
        return $this->GetAllData();
        /*$path = "https://bazaraservices.mahaksoft.com/sync/GetProducts?systemSyncID=2057&changedAfter=100025&userToken=".Yii::$app->Mahak->login();
        $arrContextOptions=array(
            "ssl"=>array(
                'verify_peer' => false,
                'verify_peer_name' => false
            ),
        );
        $item = json_decode(file_get_contents($path, FALSE, stream_context_create($arrContextOptions)), true);

        if($item['data'] != NULL)
        {
            foreach($item['data'] as $key=>$value)
            {
                $productID = $value["ProductID"];
                $code = $value["Code"];

                $product = Product::find()->where(["code"=>$code])->one();
                if($product != NULL && $product->productid != $productID)
                {
                    $product->productid = intval($productID);
                    $product->level2 = 123456;
                    $product->save();
                }
            }
        }*/

    }

    public function products22()
    {
        return $this->GetAllData();
        /*$path = "https://bazaraservices.mahaksoft.com/sync/GetPrices?systemSyncID=2057&changedAfter=100025&userToken=".Yii::$app->Mahak->login();
        $arrContextOptions=array(
            "ssl"=>array(
                'verify_peer' => false,
                'verify_peer_name' => false
            ),
        );
        $item = json_decode(file_get_contents($path, FALSE, stream_context_create($arrContextOptions)), true);

        foreach($item as $key=>$value)
        {
            echo "<pre>";
            var_dump($value);
            echo "<hr>";
        }*/

    }

    public function products33()
    {
        return $this->GetAllData();
        // die(var_dump(Yii::$app->Mahak->login()));
        /*$path = "https://bazaraservices.mahaksoft.com/sync/GetProducts?systemSyncID=2057&changedAfter=100025&userToken=".Yii::$app->Mahak->login();
        $arrContextOptions=array(
            "ssl"=>array(
                'verify_peer' => false,
                'verify_peer_name' => false
            ),
        );
        $item = json_decode(file_get_contents($path, FALSE, stream_context_create($arrContextOptions)), true);

        foreach($item as $key=>$value)
        {
            echo "<pre>";
            var_dump($value);
            echo "<hr>";
        }*/

    }

    // بروزرسانی قیمت ها 
    public function prices()
    {
        return $this->GetAllData();
        /*Yii::$app->Mahak->products();

        $path = "https://bazaraservices.mahaksoft.com/sync/GetPrices?systemSyncID=2057&changedAfter=100025&userToken=".Yii::$app->Mahak->login();
        $arrContextOptions=array(
            "ssl"=>array(
                'verify_peer' => false,
                'verify_peer_name' => false
            ),
        );
        $item = json_decode(file_get_contents($path, FALSE, stream_context_create($arrContextOptions)), true);

        if($item['data'] != NULL)
        {
            foreach($item['data'] as $key=>$value)
            {
                $ProductID = $value["ProductID"];
                $AvailableCount = $value["AvailableCount"];
                $PriceId = $value["ProductPriceID"];
                $Price1 = $value["Price1"];
                $Price2 = $value["Price2"];
                $Price3 = $value["Price3"];

                $product = Product::find()->where(["productid"=>$ProductID])->one();
                if($product != NULL && $product->type == 1)
                {
                    if($product->price != $Price2 || $product->counter != $AvailableCount || $product->price2 != $Price1 || $product->productpriceid != $PriceId)
                    {
                        $product->price = intval($Price2);
                        $product->price2 = intval($Price1);
                        if(($AvailableCount - 100) > 0)
                        {
                            $product->counter = intval($AvailableCount - 100);
                        }
                        else
                        {
                            if($product->id == 933 || $product->id == 642 || $product->id == 934 || $product->id == 938)
                            {
                                $product->counter = $AvailableCount;
                            }
                            else
                            {
                                $product->counter = 0;

                            }
                        }
                        $product->productpriceid = intval($PriceId);
                        $product->level2 = 123456;
                        if($product->counter > 0)
                        {
                            $product->count = 1;
                        }
                        else
                        {
                            $product->count = 0;
                        }

                        $product->save();
                    }
                }
                elseif($product != NULL && $product->type == 2)
                {
                    if($product->price != $Price3 || $product->counter != $AvailableCount || $product->productpriceid != $PriceId)
                    {
                        $product->price = intval($Price3); 
                        if(($AvailableCount - 12) > 0)
                        {
                            $product->counter = intval($AvailableCount - 12);   

                        }
                        else
                        {
                            if($product->id == 933 || $product->id == 642 || $product->id == 934 || $product->id == 938)
                            {
                                $product->counter = $AvailableCount;
                            }
                            else
                            {
                                $product->counter = 0;
                            }
                        }
                        $product->productpriceid = intval($PriceId);
                        $product->level2 = 123456;
                        if($product->counter > 0)
                        {
                            $product->count = 1;
                        }
                        else
                        {
                            $product->count = 0;
                        }
                        $product->save();
                    }
                }
            }
        }*/
    }

    // کاربران نرم افزار محک
    public function users()
    {
        return $this->GetAllData();
        /*$path = "https://bazaraservices.mahaksoft.com/sync/GetPeople?systemSyncID=2057&changedAfter=100025&userToken=".Yii::$app->Mahak->login();
        $arrContextOptions=array(
            "ssl"=>array(
                'verify_peer' => false,
                'verify_peer_name' => false
            ),
        );
        $item = json_decode(file_get_contents($path, FALSE, stream_context_create($arrContextOptions)), true);

        if($item['data'] != NULL)
        {
            foreach($item['data'] as $key=>$value)
            {
                $personID = $value["PersonID"];
                $code = $value["PersonCode"];



                $user = User::find()->where(['personcode'=>$code])->one();
                if($user != NULL && $user->personid != $personID)
                {
                    $user->personid = intval($personID);
                    if(!$user->save())
                    {
                        die(var_dump($user->errors));
                    }
                }
            }
        }*/

    }

    // ثبت فاکتور جدیددر بازار 1
    public function factors($orders, $amount)
    {
        // die(var_dump(Yii::$app->user->identity->personid));
        $param = [
            "systemSyncID" => 2057,
            "userToken" => Yii::$app->Mahak->login(),
            "Person" => [
                "PersonId" => Yii::$app->user->identity->personid,
            ],
            "ShippingAddress" => [
                "AddressID" => 0,
                "CityCode" => 102000,
                "PostalCode" => "1234567890",
                "AddressLabel" => "----AA----",
                "Title" => "Good Address",
                "Phone" => +989152290090,
                "Longitude" => 0,
                "Latitude" => 0,
                "Comment" => "",
            ],
            "Order" => [
                "OrderDate" => date("Y-m-d H:i:s"),
                "Status" => 0,
                "CustomerComment" => "Online Order 1",
                "comment" => "Online Order 2",
                "PaymentType" => 1,
            ],
            "Items" => $orders,
            "ExtraCosts" => [
                "Amount" => 0,
                "Type" => 1,
                "Description" => "Online Order 100",
            ],
            "Transactions" => [
                "DebtAmount" => 0,
                "CreditAmount" => $amount,
                "TransactionType" => 0,
                "RefrenceNumber" => 1234567890,
                "TransactionDate" => date("Y-m-d H:i:s"),
                "Account" => "mellat:1236547890",
                "Comment" => "Online Order 2000",
            ],
        ];

        $url = "https://bazaraservices.mahaksoft.com/sync/CreateNewOrder";
        $options = array(
            'http' => array(
                'method' => 'POST',
                'content' => json_encode($param),
                'header' => "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n"
            ),
            "ssl" => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            ),
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $response = json_decode($result);

        return $response;
    }



}