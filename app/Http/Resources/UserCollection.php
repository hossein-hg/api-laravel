<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Carbon\Carbon;
class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $users = User::all();
        
        $all_users_count = $users->count();
        $all_inactive_users_count = $users->where('is_active', 0)->count();
        $all_regular_users_count = $users->where('user_type','regular')->count();
        $all_legal_users_count = $users->where('user_type','legal')->count();

        $currentMonthUsers = $users->whereBetween('created_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ])->count();
        $lastMonthUsers = $users->whereBetween('created_at', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        ])->count();
        if ($lastMonthUsers > 0) {
            $percentageIncreaseAllUsers = (($currentMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100;
            $percentageIncreaseAllUsers = min($percentageIncreaseAllUsers, 100);
        } else {
            $percentageIncreaseAllUsers = $currentMonthUsers > 0 ? 100 : 0;
            
        }

        $currentMonthUsers = $users->where('is_active', 0)->whereBetween('created_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ])->count();
        $lastMonthUsers = $users->where('is_active', 0)->whereBetween('created_at', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        ])->count();
        if ($lastMonthUsers > 0) {
            $percentageIncreaseInActiveUsers = (($currentMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100;
            $percentageIncreaseInActiveUsers = min($percentageIncreaseInActiveUsers, 100);
        } else {
            $percentageIncreaseInActiveUsers = $currentMonthUsers > 0 ? 100 : 0;
        }

        $currentMonthUsers = $users->where('user_type', 'regular')->whereBetween('created_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ])->count();
        $lastMonthUsers = $users->where('user_type', 'regular')->whereBetween('created_at', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        ])->count();
        if ($lastMonthUsers > 0) {
            $percentageIncreaseReqularUsers = (($currentMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100;
            $percentageIncreaseReqularUsers = min($percentageIncreaseReqularUsers, 100);
        } else {
            $percentageIncreaseReqularUsers = $currentMonthUsers > 0 ? 100 : 0;
        }

        $currentMonthUsers = $users->where('user_type', 'legal')->whereBetween('created_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ])->count();
        $lastMonthUsers = $users->where('user_type', 'legal')->whereBetween('created_at', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        ])->count();
        if ($lastMonthUsers > 0) {
            $percentageIncreaseLegalUsers = (($currentMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100;
            $percentageIncreaseLegalUsers = min($percentageIncreaseLegalUsers, 100);
        } else {
            $percentageIncreaseLegalUsers = $currentMonthUsers > 0 ? 100 : 0;
        }

        return [
            'data' => [
                'results' => UserResource::collection($this->collection),
                'list'=> [
                    [
                        'title'=> 'تعداد کل کاربران',
                        'count' => $all_users_count,
                        'percentageIncrease' => $percentageIncreaseAllUsers,
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
                        'count' => $all_inactive_users_count,
                        'percentageIncrease' => $percentageIncreaseInActiveUsers,
                        'svg'=> '  <svg
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
                        'title' => 'کاربران حقیقی',
                        'count' => $all_regular_users_count,
                        'percentageIncrease' => $percentageIncreaseReqularUsers,
                        'svg'=> '<svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="1em"
                            height="1em"
                            fill="currentColor"
                            stroke="currentColor"
                            strokeWidth="0"
                            viewBox="0 0 448 512"
                        >
                            <path
                            stroke="none"
                            d="M313.6 304c-28.7 0-42.5 16-89.6 16s-60.8-16-89.6-16C60.2 304 0 364.2 0 438.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-25.6c0-74.2-60.2-134.4-134.4-134.4M400 464H48v-25.6c0-47.6 38.8-86.4 86.4-86.4 14.6 0 38.3 16 89.6 16 51.7 0 74.9-16 89.6-16 47.6 0 86.4 38.8 86.4 86.4zM224 288c79.5 0 144-64.5 144-144S303.5 0 224 0 80 64.5 80 144s64.5 144 144 144m0-240c52.9 0 96 43.1 96 96s-43.1 96-96 96-96-43.1-96-96 43.1-96 96-96"
                            ></path>
                        </svg>'
                    ],
                    [
                        'title' => 'کاربران حقوقی',
                        'count' => $all_legal_users_count,
                        'percentageIncrease' => $percentageIncreaseLegalUsers,
                        'svg'=> '<svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="1em"
                        height="1em"
                        fill="currentColor"
                        stroke="currentColor"
                        strokeWidth="0"
                        viewBox="0 0 448 512"
                    >
                        <path
                        stroke="none"
                        d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128m95.8 32.6L272 480l-32-136 32-56h-96l32 56-32 136-47.8-191.4C56.9 292 0 350.3 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-72.1-56.9-130.4-128.2-133.8"
                        ></path>
                    </svg>'
                    ],
                ],
                
                'hasPrevPage' => !$this->onFirstPage(),
                'hasNextPage' => $this->hasMorePages(),
                'page' => $this->currentPage(),
                'total_page' => $this->lastPage(),
                'total_users' => $this->total(),
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];
    }

    public function paginationInformation($request, $paginated, $default)
    {
        unset($default['links']);
        unset($default['meta']);

        return $default;
    }
}
