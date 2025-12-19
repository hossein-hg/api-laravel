<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Admin\Order;
use Illuminate\Http\Request;
use App\Models\Admin\RoleLog;
use App\Http\Resources\OrederProductResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminHomeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    
        
    
    public function toArray(Request $request): array
    {
        $users = User::all();
        $all_users_count = $users->count();
        $all_inactive_users_count = $users->where('is_active', 0)->count();
        $all_regular_users_count = $users->where('user_type', 'regular')->count();
        $all_legal_users_count = $users->where('user_type', 'legal')->count();

        $orders = Order::query();
        $allOrdersCount = $orders->count();
        $ordersNotApprovedCount = $orders->where('status', 5)->count();
        $finalOrdersCount = $orders->where('status', 7)->count();
        $todayOrders = Order::where('status', 7)
            ->whereDate('created_at', Carbon::today())
            ->with('products')
            ->get();

        $allUniqueProducts = $todayOrders
            ->pluck('products')
            ->flatten()
            ->unique('id')
            ->values(); // ← این Collection اصلی محصولات منحصربه‌فرد

        // تنظیمات صفحه‌بندی
        $perPage = 1; 
        $currentPage = LengthAwarePaginator::resolveCurrentPage() ?: 1;

        // صفحه فعلی رو از Collection اصلی برش بزن
        $paginatedItems = $allUniqueProducts->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $uniqueProductsToday = new LengthAwarePaginator(
            $paginatedItems,                    // ← محصولات صفحه فعلی
            $allUniqueProducts->count(),        // ← تعداد کل محصولات منحصربه‌فرد
            $perPage,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),  // مهم: برای حفظ پارامترهای query string مثل ?page=2
            ]
        );
        $latestLogs = RoleLog::latest()->limit(3)->get();
        $logsForJson = $latestLogs->map(function ($log) {
            return [
                'id' => $log->id,
                'ip' => $log->ip,
                'user' => $log->user ? $log->user->name : null,
                'role' => $log->role,
                'description' => $log->description,
                'created_at' => $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : null,
            ];
        })->toArray();
        $year = now()->year; // سال میلادی جاری

        // ۱. آمار واقعی بر اساس ماه میلادی
        $registrations = User::whereYear('created_at', $year)
            ->selectRaw('COUNT(*) as count, MONTH(created_at) as month')
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray(); // مثلاً [1 => 10, 2 => 15, 3 => 20, 4 => 45, ...]

        // ۲. آرایه پایه ۱۲ ماهه با مقدار ۰ (برای ماه‌های شمسی: ۱=فروردین تا ۱۲=اسفند)
        $monthlyRegistrations = array_fill(1, 12, 0); // اندیس ۱ تا ۱۲

        // ۳. شیفت دادن: ماه میلادی → ماه شمسی (تقریبی)
        foreach ($registrations as $gregorianMonth => $count) {
            if ($gregorianMonth >= 4) {
                // فروردین تا آذر (ماه‌های ۴ تا ۱۲ میلادی → ۱ تا ۹ شمسی)
                $jalaliMonth = $gregorianMonth - 3;
            } else {
                // دی تا اسفند (ماه‌های ۱ تا ۳ میلادی → ۱۰ تا ۱۲ شمسی)
                $jalaliMonth = $gregorianMonth + 9;
            }

            $monthlyRegistrations[$jalaliMonth] = $count;
        }

        $label = ['فروردین',
        'اردیبهشت',
        'خرداد',
        'تیر',
        'مرداد',
        'شهریور',
        'مهر',
        'آبان',
        'آذر',
        'دی',
        'بهمن',
        'اسفند',
    ];




         
        return [
            'data'=> [
                'products' => [
                    'results' => OrederProductResource::collection($uniqueProductsToday),
                    'hasPrevPage' => !$uniqueProductsToday->onFirstPage(),
                    'hasNextPage' => $uniqueProductsToday->hasMorePages(),
                    'page' => $uniqueProductsToday->currentPage(),
                    'total_page' => $uniqueProductsToday->lastPage(),

                    'total_products' => $uniqueProductsToday->total(),
                ],
                'latestsLogs'=> $logsForJson,
                'bar_chart_data'=> [
                    'labels'=> $label,
                    'data'=> $monthlyRegistrations,
                ],
                'list'=> [
                    [
                        'title'=> 'تعداد کل کاربران',
                        'count' => (string)$all_users_count,
                        'subtitle'=> 'تعداد کل کاربران سایت',
                        'percentageIncrease' => 20,
                        'svg'=> ' <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="1em"
                                height="1em"
                                fill="none"
                                stroke="currentColor"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                viewBox="0 0 24 24"
                            >
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>'
                    ],
                    [
                        'title' => 'کاربران غیرفعال ',
                        'count' => (string)$all_inactive_users_count,
                        'subtitle' => ' نسبت کاربران غیرفعال نسبت به کل',
                        'percentageIncrease' => 15,
                        'svg' => '  <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="1em"
                        height="1em"
                        fill="currentColor"
                        stroke="currentColor"
                        strokeWidth="0"
                        viewBox="0 0 512 512"
                    >
                        <path
                        stroke="none"
                        d="m405.822 78.899 24.444 24.444L100.485 433.1 76.04 408.657zM168.32 255.677a87.704 87.704 0 0 1 117.196-82.575l43.402-43.402A236.4 236.4 0 0 0 256 118.452a239.7 239.7 0 0 0-84.454 15.616 271 271 0 0 0-38.861 18.59 293 293 0 0 0-34.816 23.821 312 312 0 0 0-29.423 26.507 336 336 0 0 0-22.681 25.355l-4.46 5.554-3.93 5.267c-2.443 3.204-4.518 6.224-6.2 8.678s-2.974 4.541-3.85 5.855L26 255.758l1.325 2.063c.876 1.325 2.167 3.457 3.85 5.854 1.682 2.398 3.757 5.475 6.2 8.679l3.93 5.266 4.46 5.555a336 336 0 0 0 22.68 25.355 312 312 0 0 0 29.424 26.507q7.145 5.67 14.82 11.018l60.736-60.736a87.5 87.5 0 0 1-5.106-29.642zm316.367-2.086c-.876-1.337-2.166-3.515-3.85-5.889-1.682-2.374-3.756-5.509-6.2-8.736-2.443-3.457-5.255-6.995-8.39-10.867a340 340 0 0 0-22.68-25.459 312 312 0 0 0-29.423-26.564 306 306 0 0 0-17.587-12.954l-59.375 59.375a87.692 87.692 0 0 1-114.35 114.35l-43.31 43.31A240 240 0 0 0 256 392.913a236.8 236.8 0 0 0 84.454-15.258 269 269 0 0 0 38.861-18.544 290 290 0 0 0 34.816-23.822 312 312 0 0 0 29.423-26.564 340 340 0 0 0 22.681-25.458c3.146-3.884 5.947-7.457 8.39-10.868 2.443-3.227 4.518-6.247 6.2-8.736s2.974-4.61 3.85-5.89L486 255.69z"
                        ></path>
                    </svg>'
                    ],
                    [
                        'title' => 'تعداد کل سفارشات',
                        'count' => (string) $allOrdersCount,
                        'subtitle' => 'تعداد  تمام سفارشات موجود',
                        'percentageIncrease' => 5,
                        'svg' => ' <svg xmlns="http://www.w3.org/2000/svg" width="42px" height="42px" viewBox="0 0 24 24">
<path fill="#2ed47e" d="M19.437 18.218H4.563a2.5 2.5 0 0 1-2.5-2.5V8.282a2.5 2.5 0 0 1 2.5-2.5h14.874a2.5 2.5 0 0 1 2.5 2.5v7.436a2.5 2.5 0 0 1-2.5 2.5M4.563 6.782a1.5 1.5 0 0 0-1.5 1.5v7.436a1.5 1.5 0 0 0 1.5 1.5h14.874a1.5 1.5 0 0 0 1.5-1.5V8.282a1.5 1.5 0 0 0-1.5-1.5Z" />
<path fill="#2ed47e" d="M12 12.786H5.064a.5.5 0 0 1 0-1H12a.5.5 0 0 1 0 1m2 2.928H5.064a.5.5 0 1 1 0-1H14a.5.5 0 0 1 0 1" />
<rect width="4" height="2" x="15.436" y="8.283" fill="#2ed47e" rx=".5" />
</svg>
                            '
                    ],
                    [
                        'title' => 'سفارشات در انتظار تایید',
                        'subtitle' => 'تعداد  تمام سفارشات در انتظار تایید',
                        'count' => (string) $ordersNotApprovedCount,
                        'percentageIncrease' => 10,
                        'svg' => '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><g id="View_List"><g><path d="M18.436,20.937H5.562a2.5,2.5,0,0,1-2.5-2.5V5.563a2.5,2.5,0,0,1,2.5-2.5H18.436a2.5,2.5,0,0,1,2.5,2.5V18.437A2.5,2.5,0,0,1,18.436,20.937ZM5.562,4.063a1.5,1.5,0,0,0-1.5,1.5V18.437a1.5,1.5,0,0,0,1.5,1.5H18.436a1.5,1.5,0,0,0,1.5-1.5V5.563a1.5,1.5,0,0,0-1.5-1.5Z"></path><path d="M6.544,8.283h0a.519.519,0,0,1-.353-.147.5.5,0,0,1,0-.707.512.512,0,0,1,.353-.146H7.55a.516.516,0,0,1,.353.146.5.5,0,0,1,.147.354.5.5,0,0,1-.5.5Z"></path><path d="M6.544,12.5h0a.523.523,0,0,1-.353-.146.5.5,0,0,1,0-.708.516.516,0,0,1,.353-.146H7.55a.521.521,0,0,1,.353.146.5.5,0,0,1,0,.708.516.516,0,0,1-.353.146Z"></path><path d="M6.544,16.72h0a.519.519,0,0,1-.353-.147.5.5,0,0,1,0-.707.516.516,0,0,1,.353-.146H7.55a.516.516,0,0,1,.353.146.5.5,0,0,1,.147.354.5.5,0,0,1-.5.5Z"></path><path d="M10.554,8.281h0a.5.5,0,0,1,0-1h6.9a.5.5,0,0,1,0,1Z"></path><path d="M10.554,12.5h0a.5.5,0,0,1,0-1h6.9a.5.5,0,0,1,0,1Z"></path><path d="M10.554,16.718h0a.5.5,0,0,1,0-1h6.9a.5.5,0,0,1,0,1Z"></path></g></g></svg>'
                    ],
                ]
            ]
        ];
    }
}
