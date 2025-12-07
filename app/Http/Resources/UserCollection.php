<?php

namespace App\Http\Resources;

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
        $all_users_count = $this->collection->count();
        $all_active_users_count = $this->collection->where('is_active', 1)->count();
        $all_regular_users_count = $this->collection->where('user_type','regular')->count();
        $all_legal_users_count = $this->collection->where('user_type','legal')->count();

        $currentMonthUsers = $this->collection->whereBetween('created_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ])->count();
        $lastMonthUsers = $this->collection->whereBetween('created_at', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        ])->count();
        if ($lastMonthUsers > 0) {
            $percentageIncreaseAllUsers = (($currentMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100;
        } else {
            $percentageIncreaseAllUsers = $currentMonthUsers > 0 ? 100 : 0;
        }

        $currentMonthUsers = $this->collection->where('is_active', 1)->whereBetween('created_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ])->count();
        $lastMonthUsers = $this->collection->where('is_active', 1)->whereBetween('created_at', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        ])->count();
        if ($lastMonthUsers > 0) {
            $percentageIncreaseActiveUsers = (($currentMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100;
        } else {
            $percentageIncreaseActiveUsers = $currentMonthUsers > 0 ? 100 : 0;
        }

        $currentMonthUsers = $this->collection->where('user_type', 'regular')->whereBetween('created_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ])->count();
        $lastMonthUsers = $this->collection->where('user_type', 'regular')->whereBetween('created_at', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        ])->count();
        if ($lastMonthUsers > 0) {
            $percentageIncreaseReqularUsers = (($currentMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100;
        } else {
            $percentageIncreaseReqularUsers = $currentMonthUsers > 0 ? 100 : 0;
        }

        $currentMonthUsers = $this->collection->where('user_type', 'legal')->whereBetween('created_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ])->count();
        $lastMonthUsers = $this->collection->where('user_type', 'legal')->whereBetween('created_at', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        ])->count();
        if ($lastMonthUsers > 0) {
            $percentageIncreaseLegalUsers = (($currentMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100;
        } else {
            $percentageIncreaseLegalUsers = $currentMonthUsers > 0 ? 100 : 0;
        }



       

        return [
            'data' => [
                'results' => UserResource::collection($this->collection),
                'all_users_count'=> $all_users_count,
                'all_active_users_count'=> $all_active_users_count,
                'all_regular_users_count'=> $all_regular_users_count,
                'all_legal_users_count'=> $all_legal_users_count,
                'percentageIncreaseAllUsers'=> $percentageIncreaseAllUsers,
                'percentageIncreaseActiveUsers'=> $percentageIncreaseActiveUsers,
                'percentageIncreaseReqularUsers'=> $percentageIncreaseReqularUsers,
                'percentageIncreaseLegalUsers'=> $percentageIncreaseLegalUsers,
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
