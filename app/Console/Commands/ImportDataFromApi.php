<?php

namespace App\Console\Commands;
use Illuminate\Support\Facades\Http;
use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class ImportDataFromApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ApiDatabase';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get Data from api for Database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $respone = Http::get("https://randomuser.me/api/?results=10");

        if ($respone->successful() == true) {
            $rows = $respone->json();
            foreach ( $rows["results"] as $row )  {
                $email = $row["email"];
                $username = $row["login"]["username"];
                $password = Hash::make($row["login"]["password"]);
                User::create(['email'=>$email,'name'=>$username,'password'=>$password]);

            }

        }   else {
            $this->error("Error");
        }
    }
}
