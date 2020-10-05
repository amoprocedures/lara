<?php namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class API extends Controller
{
    public $Mailer = [];

    public function __construct()
    {
        $this->Mailer = [
            'IFrom' => ['email' => 'thebridgeug01@gmail.com', 'title' => 'The Bridge'],
            'IReceiver' => ['email' => '', 'title' => ''],
            'ISubject' => '',
            'IHtml' => ''
        ];
    }

    public function UUID()
    {
        $uuid = DB::select('SELECT uuid() as code')[0]->code;
        return $uuid;
    }

    public static function NOW($format = 'dt')
    {
        date_default_timezone_set('Africa/Nairobi');
        $output = Date("Y-m-d\TH:i:s");
        if ($format == 'd') {
            return Date("Y-m-d");
        }
        if ($format == 't') {
            return Date("H:i:s");
        }
        return $output;
    }

    public function make_response($status = false, $message = '', $error = null, $response = null)
    {
        return ['status' => $status, 'message' => $message, 'error' => $error, 'response' => $response];
    }

    public function RANDOM_KEY($length = 4, $numeric = false)
    {
        $characters = ($numeric) ? '123456789' : '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function SEND_MAIL(Array $IReceiver, string $ISubject, string $IHtml, Array $IFrom = [])
    {
        date_default_timezone_set('Africa/Nairobi');

        if (!empty($IReceiver) && array_key_exists('email', $IReceiver) && array_key_exists('title', $IReceiver)) {
            $this->Mailer['IReceiver'] = $IReceiver;
        }

        if (!empty($IFrom) && array_key_exists('email', $IFrom) && array_key_exists('title', $IFrom)) {
            $this->Mailer['IFrom'] = $IFrom;
        }

        if (!empty($ISubject)) {
            $this->Mailer['ISubject'] = $ISubject;
        }

        if (!empty($IHtml)) {
            $this->Mailer['IHtml'] = $IHtml;
        }

        $data = ['name' => $this->Mailer['IReceiver']['title'], 'html' => $this->Mailer['IHtml']];

        Mail::send('mail', $data, function ($message) {
            $message
                ->to($this->Mailer['IReceiver']['email'], $this->Mailer['IReceiver']['title'])
                ->subject($this->Mailer['ISubject'])
                ->from($this->Mailer['IFrom']['email'], $this->Mailer['IFrom']['title']);
        });
        return self::make_response(true, 'OK', null, 'Email has been sent successfully!');
    }

}
