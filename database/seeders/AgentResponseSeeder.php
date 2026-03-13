<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AgentResponse;
use Carbon\Carbon;

class AgentResponseSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $twoDaysAgo = Carbon::today()->subDays(2)->format('Y-m-d');
        
        $data = [
            ['customer_name' => 'M1cH_N1k1J', 'agent_name' => 'Matthew', 'date' => $today, 'first_response_time' => '00:02:31', 'average_response_time' => '06:27:50', 'resolved_time' => '06:27:50'],
            ['customer_name' => 'DIMSUMNESIA', 'agent_name' => 'Matthew', 'date' => $today, 'first_response_time' => '00:02:23', 'average_response_time' => '01:25:25', 'resolved_time' => '01:25:25'],
            ['customer_name' => 'Masdoo', 'agent_name' => 'Matthew', 'date' => $today, 'first_response_time' => '00:03:40', 'average_response_time' => '00:42:53', 'resolved_time' => '00:42:53'],
            ['customer_name' => 'pradan 0310', 'agent_name' => 'Matthew', 'date' => $today, 'first_response_time' => '00:00:44', 'average_response_time' => '00:26:55', 'resolved_time' => '00:26:55'],
            ['customer_name' => 'Kwang Sen', 'agent_name' => 'Matthew', 'date' => $yesterday, 'first_response_time' => '00:00:32', 'average_response_time' => '00:08:35', 'resolved_time' => '00:08:35'],
            ['customer_name' => 'esacatering842', 'agent_name' => 'Matthew', 'date' => $yesterday, 'first_response_time' => '00:37:00', 'average_response_time' => '00:02:38', 'resolved_time' => '00:02:38'],
            ['customer_name' => 'Rina Store', 'agent_name' => 'Jessica', 'date' => $yesterday, 'first_response_time' => '00:01:15', 'average_response_time' => '00:15:22', 'resolved_time' => '00:15:22'],
            ['customer_name' => 'Andi Pratama', 'agent_name' => 'Jessica', 'date' => $yesterday, 'first_response_time' => '00:00:55', 'average_response_time' => '00:10:44', 'resolved_time' => '00:10:44'],
            ['customer_name' => 'Sari Collection', 'agent_name' => 'Daniel', 'date' => $twoDaysAgo, 'first_response_time' => '00:04:21', 'average_response_time' => '00:55:12', 'resolved_time' => '00:55:12'],
            ['customer_name' => 'Techno Mart', 'agent_name' => 'Daniel', 'date' => $twoDaysAgo, 'first_response_time' => '00:02:05', 'average_response_time' => '00:18:45', 'resolved_time' => '00:18:45'],
        ];

        // Hapus data lama jika ada
        AgentResponse::truncate();
        
        foreach ($data as $item) {
            AgentResponse::create($item);
        }
    }
}
