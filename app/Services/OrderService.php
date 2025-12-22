<?php
namespace App\Services;
use App\Models\Admin\Order;
use Illuminate\Support\Facades\DB;

class OrderService{
    public function getOrdersByStatus($method = null, $status = null){
      
        if ($method == 'index'){
            $query = Order::with('products', 'user')->where('user_id', auth()->user()->id);
            $orders = $query->orderBy('id', 'desc')->paginate(10);
           
            return $orders;
        }
        else {
            $query = Order::with('products', 'user');
            $orders = $query->orderBy('id', 'desc')->whereIn('status', $status)->paginate(10);
            return $orders;
        }
    }


    public function detail($order){
        $user = $order->user;

        $credit_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'credit')->count();
        $cash_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'cash')->count();
        $result = DB::table('order_product')
            ->where('order_id', $order->id)
            ->whereIn('pay_type', ['day_30', 'day_60', 'day_90', 'day_120', 'day_45', 'day_75', 'day_180']) // فقط day_* ها
            ->select('pay_type', DB::raw('count(distinct pay_type) as total'))
            ->groupBy('pay_type')
            ->get();

        // جمع کل day_* ها
        $check_count = $result->sum('total');
        $credit_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'credit')->sum('price'));
        $cash_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'cash')->sum('price'));
        $result = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')
            ->select('pay_type', DB::raw('COUNT(*) as total'), DB::raw('SUM(price) as total_price'))
            ->groupBy('pay_type')
            ->get();
        $checkes = $result->map(function ($item) {
            return [

                'pay_type' => $item->pay_type,
                'total_price' => number_format($item->total_price)
            ];
        });


    }


}