<?php

namespace App\Http\Resources;

use App\Models\Admin\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        
        if (request()->route()->getName() == 'orders-admin-all'){
            $orders = Order::query();
            $allOrdersCount = $orders->count(); 
            $ordersNotApprovedCount = $orders->where('status',5)->count();
            $finalOrdersCount = $orders->where('status',7)->count();

            $userId = Order::query()
                ->select('user_id')
                ->groupBy('user_id')
                ->orderByRaw('COUNT(*) DESC')
                ->value('user_id');
            $name = User::where('id',$userId)->first()->name;

            return [
                'data' => [
                    'results' => OrderListResource::collection($this->collection),
                    'hasPrevPage' => !$this->onFirstPage(),
                    'hasNextPage' => $this->hasMorePages(),
                    'page' => $this->currentPage(),
                    'total_page' => $this->lastPage(),
                    'total_orders' => $this->total(),
                    'list'=> [
                        [
                            'title'=> 'تعداد کل سفارشات',
                            'subtitle' => 'نسبت به ماه قبل',
                            'count' => (string) $allOrdersCount,
                            // 'percentageIncrease' => $percentageIncreaseAllUsers,
                            'svg'=> ' <svg xmlns="http://www.w3.org/2000/svg" width="42px" height="42px" viewBox="0 0 24 24">
<path fill="#2ed47e" d="M19.437 18.218H4.563a2.5 2.5 0 0 1-2.5-2.5V8.282a2.5 2.5 0 0 1 2.5-2.5h14.874a2.5 2.5 0 0 1 2.5 2.5v7.436a2.5 2.5 0 0 1-2.5 2.5M4.563 6.782a1.5 1.5 0 0 0-1.5 1.5v7.436a1.5 1.5 0 0 0 1.5 1.5h14.874a1.5 1.5 0 0 0 1.5-1.5V8.282a1.5 1.5 0 0 0-1.5-1.5Z" />
<path fill="#2ed47e" d="M12 12.786H5.064a.5.5 0 0 1 0-1H12a.5.5 0 0 1 0 1m2 2.928H5.064a.5.5 0 1 1 0-1H14a.5.5 0 0 1 0 1" />
<rect width="4" height="2" x="15.436" y="8.283" fill="#2ed47e" rx=".5" />
</svg>
                            '
                        ],
                        [
                            'title' => 'سفارشات در انتظار تایید',
                            'subtitle' => 'نسبت به ماه قبل',
                            'count' => (string) $ordersNotApprovedCount,
                            // 'percentageIncrease' => $percentageIncreaseAllUsers,
                            'svg' => '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><g id="View_List"><g><path d="M18.436,20.937H5.562a2.5,2.5,0,0,1-2.5-2.5V5.563a2.5,2.5,0,0,1,2.5-2.5H18.436a2.5,2.5,0,0,1,2.5,2.5V18.437A2.5,2.5,0,0,1,18.436,20.937ZM5.562,4.063a1.5,1.5,0,0,0-1.5,1.5V18.437a1.5,1.5,0,0,0,1.5,1.5H18.436a1.5,1.5,0,0,0,1.5-1.5V5.563a1.5,1.5,0,0,0-1.5-1.5Z"></path><path d="M6.544,8.283h0a.519.519,0,0,1-.353-.147.5.5,0,0,1,0-.707.512.512,0,0,1,.353-.146H7.55a.516.516,0,0,1,.353.146.5.5,0,0,1,.147.354.5.5,0,0,1-.5.5Z"></path><path d="M6.544,12.5h0a.523.523,0,0,1-.353-.146.5.5,0,0,1,0-.708.516.516,0,0,1,.353-.146H7.55a.521.521,0,0,1,.353.146.5.5,0,0,1,0,.708.516.516,0,0,1-.353.146Z"></path><path d="M6.544,16.72h0a.519.519,0,0,1-.353-.147.5.5,0,0,1,0-.707.516.516,0,0,1,.353-.146H7.55a.516.516,0,0,1,.353.146.5.5,0,0,1,.147.354.5.5,0,0,1-.5.5Z"></path><path d="M10.554,8.281h0a.5.5,0,0,1,0-1h6.9a.5.5,0,0,1,0,1Z"></path><path d="M10.554,12.5h0a.5.5,0,0,1,0-1h6.9a.5.5,0,0,1,0,1Z"></path><path d="M10.554,16.718h0a.5.5,0,0,1,0-1h6.9a.5.5,0,0,1,0,1Z"></path></g></g></svg>'
                        ],
                        [
                            'title' => 'سفارشات نهایی شده',
                            'count' => (string) $finalOrdersCount,
                            'subtitle' => 'نسبت به ماه قبل',
                            // 'percentageIncrease' => $percentageIncreaseAllUsers,
                            'svg' => '<svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    width="1em"
                                                    height="1em"
                                                    fill="currentColor"
                                                    stroke="currentColor"
                                                    strokeWidth="0"
                                                    className="size-5"
                                                    viewBox="0 0 16 16"
                                                >
                                                    <path
                                                    stroke="none"
                                                    d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2z"
                                                    ></path>
                                                    <path
                                                    stroke="none"
                                                    d="M7 5.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m-1.496-.854a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708l.146.147 1.146-1.147a.5.5 0 0 1 .708 0M7 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m-1.496-.854a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 0 1 .708-.708l.146.147 1.146-1.147a.5.5 0 0 1 .708 0"
                                                    ></path>
                                                </svg>'
                        ],
                        [
                            'title' => 'برترین خریدار',
                            'subtitle' => 'نسبت به ماه قبل',
                            'count' => $name,
                            // 'percentageIncrease' => $percentageIncreaseAllUsers,
                            'svg' => 
                            '<svg
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
                    
                ],
                ],
                'statusCode' => 200,
                'message' => 'موفقیت آمیز',
                'success' => true,
                'errors' => null,
            ];
            
        }
        else{
            return [
                'data' => [
                    'results' => OrderListResource::collection($this->collection),
                    'hasPrevPage' => !$this->onFirstPage(),
                    'hasNextPage' => $this->hasMorePages(),
                    'page' => $this->currentPage(),
                    'total_page' => $this->lastPage(),
                    'total_orders' => $this->total(),
                ],
                'statusCode' => 200,
                'message' => 'موفقیت آمیز',
                'success' => true,
                'errors' => null,
            ];
        }
    }

    public function paginationInformation($request, $paginated, $default)
    {
        unset($default['links']);
        unset($default['meta']);

        return $default;
    }
}
