<?php
namespace app\components;
use app\models\CompanyStock;
use app\models\Offer;
class Helpers
{
    public static function toPersianNumber($number)
    {
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return str_replace($en, $fa, $number);
    }

    public static function getPrice($product, $offered_price = true)
    {
        
        // 1. گرفتن قیمت پایه
        $stock = CompanyStock::find()
            ->where(['product_id' => $product->id])
            ->andWhere(['>', 'price', 0])           // فقط price > 0
            ->andWhere(['IS NOT', 'price', null])   // فقط price NOT NULL
            ->orderBy(['price' => SORT_ASC])        // ارزان‌ترین اول
            ->one();

        $price = ($product->count_type == 1) ? $product->price : ($stock ? $stock->price : 0);
        if ($product->count_type == null) {
            $price = $product->price;
        }
        $offer = Offer::check($product->id);
        $is_zero = $price == 0;

        // 2. محاسبه قیمت با تخفیف (اگر offer باشه)
        $final_price = $offer ? $price * (1 - $offer / 100) : $price;
        if ($product->stock == 0 && $product->count_type == 1) {
            return '<div class="price">
                <ins class="after"> ناموجود </ins>
                </div>';
        }
        // 3. خروجی بر اساس حالت
        if ($offered_price) {
            if ($offer) {
                // حالت: تخفیف + قیمت با تخفیف
                if ($is_zero) {
                    return '<div class="price">
                    <ins class="before"> ثبت نشده</ins>
                    <ins class="after">' . number_format($final_price) . ' <span>تومان</span></ins>
                </div>';
                }
                return '<div class="price">
                <ins class="before">' . number_format($price) . '</ins>
                        <ins class="after">' . number_format($final_price) . ' <span>تومان</span></ins></div>
                ';
            }

            // حالت: بدون تخفیف، فقط قیمت نهایی
            if ($is_zero) {
                return '<div class="price">
                <ins class="after"> ثبت نشده</ins>
                </div>';
            }
            return '<div class="price">
                <ins class="after price-after">' . number_format($price) . ' <span>تومان</span></ins>
             </div>';
        }

        // حالت: فقط قیمت قبل از تخفیف
        if ($is_zero) {
            return '<div class="price"><ins class="before"> ثبت نشده</ins></div>';
        }
        return '<div class="price"><ins class="before">' . number_format($price) . '</ins></div>';

    }
}
