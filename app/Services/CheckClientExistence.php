<?php

namespace App\Services;

use App\Models\Address;
use App\Models\City;
use App\Models\Client;
use App\Models\ContactRequest;
use App\Models\District;
use App\Models\Sector;

class CheckClientExistence
{
    public function RequestWithClientCheck($id)
    {
        $data = ContactRequest::where('id', '=', $id)->first();
        $data->data = json_decode($data->data, true);
        if ($data->data['phone'] !== null) {
            $check = substr($data->data['phone'], 0, 4);
            if ((string)$check === '+966') {
                $phone = str_replace('+966', '0', $data->data['phone']);
                $client = Client::where('phone', $phone)->orWhere('second_phone', $phone)->first();
                if ($client != null) {
                    $addresses = Address::where('client_id', $client->id)->get();
                    if (count($addresses) > 0) {
                        foreach ($addresses as $address) {
                            $address->city_id = City::select('id', 'name')->where('id', $address->city_id)->first();
                            $address->sector_id = Sector::select('id', 'name')->where('id', $address->sector_id)->first();
                            $address->district_id = District::select('id', 'name')->where('id', $address->district_id)->first();
                        }
                    }
                    $data->addresses = $addresses;
                    $data->client_data = $client;
                    $data->client_exist = true;
                } else {
                    $data->client_exist = false;
                }
            } elseif (substr($data->data['phone'], 0, 3) === '966' )
            {
                $phone = str_replace('966', '0', $data->data['phone']);
                $client = Client::where('phone', $phone)->orWhere('second_phone', $phone)->first();
                if ($client != null) {
                    $addresses = Address::where('client_id', $client->id)->get();
                    if (count($addresses) > 0) {
                        foreach ($addresses as $address) {
                            $address->city_id = City::select('id', 'name')->where('id', $address->city_id)->first();
                            $address->sector_id = Sector::select('id', 'name')->where('id', $address->sector_id)->first();
                            $address->district_id = District::select('id', 'name')->where('id', $address->district_id)->first();
                        }
                    }
                    $data->addresses = $addresses;
                    $data->client_data = $client;
                    $data->client_exist = true;
                } else {
                    $data->client_exist = false;
                }
            } elseif (substr($data->data['phone'], 0, 6) === '00966')
            {
                $phone = str_replace('00966', '', $data->data['phone']);
                $client = Client::where('phone', $phone)->orWhere('second_phone', $phone)->first();
                if ($client != null) {
                    $addresses = Address::where('client_id', $client->id)->get();
                    if (count($addresses) > 0) {
                        foreach ($addresses as $address) {
                            $address->city_id = City::select('id', 'name')->where('id', $address->city_id)->first();
                            $address->sector_id = Sector::select('id', 'name')->where('id', $address->sector_id)->first();
                            $address->district_id = District::select('id', 'name')->where('id', $address->district_id)->first();
                        }
                    }
                    $data->addresses = $addresses;
                    $data->client_data = $client;
                    $data->client_exist = true;
                } else {
                    $data->client_exist = false;
                }
            } elseif (substr($data->data['phone'], 0, 1) === '5')
            {
                $phone = '0' . $data->data['phone'];
                $client = Client::where('phone', $phone)->orWhere('second_phone', $phone)->first();
                if ($client != null) {
                    $addresses = Address::where('client_id', $client->id)->get();
                    if (count($addresses) > 0) {
                        foreach ($addresses as $address) {
                            $address->city_id = City::select('id', 'name')->where('id', $address->city_id)->first();
                            $address->sector_id = Sector::select('id', 'name')->where('id', $address->sector_id)->first();
                            $address->district_id = District::select('id', 'name')->where('id', $address->district_id)->first();
                        }
                    }
                    $data->addresses = $addresses;
                    $data->client_data = $client;
                    $data->client_exist = true;
                } else {
                    $data->client_exist = false;
                }
            } else {
                $phone = $data->data['phone'];
                $client = Client::where('phone', $phone)->orWhere('second_phone', $phone)->first();

                if ($client != null) {
                    $addresses = Address::where('client_id', $client->id)->get();
                    if (count($addresses) > 0) {
                        foreach ($addresses as $address) {
                            $address->city_id = City::select('id', 'name')->where('id', $address->city_id)->first();
                            $address->sector_id = Sector::select('id', 'name')->where('id', $address->sector_id)->first();
                            $address->district_id = District::select('id', 'name')->where('id', $address->district_id)->first();
                        }
                    }
                    $data->addresses = $addresses;
                    $data->client_data = $client;
                    $data->client_exist = true;
                } else {
                    $data->client_exist = false;
                }
            }
        }
        return Response(['data' => $data]);
    }

    public function AllRequestsWithClientCheck($requestType, $clientType, $paginationCount)
    {
        $data = ContactRequest::where('req_type', $requestType)->where('client_type', $clientType)->orderBy('id', 'DESC')
            ->paginate($paginationCount);
        foreach ($data as $dt) {
            $dt->data = json_decode($dt->data, true);
            if ($dt->data['phone'] !== null) {
                $check = substr($dt->data['phone'], 0, 4);
                if ((string)$check === '+966') {
                    $phone = str_replace('+966', '0', $dt->data['phone']);
                    $client = Client::where('phone', $phone)->orWhere('second_phone', $phone)->first();
                    if ($client != null) {
                        $dt->client_exist = true;
                    } else {
                        $dt->client_exist = false;
                    }
                } elseif (substr($dt->data['phone'], 0, 3) === '966' )
                {
                    $phone = str_replace('966', '0', $dt->data['phone']);
                    $client = Client::where('phone', $phone)->orWhere('second_phone', $phone)->first();
                    if ($client != null) {
                        $dt->client_exist = true;
                    } else {
                        $dt->client_exist = false;
                    }
                } elseif (substr($dt->data['phone'], 0, 6) === '00966')
                {
                    $phone = str_replace('00966', '', $dt->data['phone']);
                    $client = Client::where('phone', $phone)->orWhere('second_phone', $phone)->first();
                    if ($client != null) {
                        $dt->client_exist = true;
                    } else {
                        $dt->client_exist = false;
                    }
                } elseif (substr($dt->data['phone'], 0, 1) === '5')
                {
                    $phone = '0' . $dt->data['phone'];
                    $client = Client::where('phone', $phone)->orWhere('second_phone', $phone)->first();
                    if ($client != null) {
                        $dt->client_exist = true;
                    } else {
                        $dt->client_exist = false;
                    }
                } else {
                    $phone = $dt->data['phone'];
                    $client = Client::where('phone', $phone)->orWhere('second_phone', $phone)->first();
                    if ($client != null) {
                        $dt->client_exist = true;
                    } else {
                        $dt->client_exist = false;
                    }
                }
            }
        }
        return Response(['data' => $data]);
    }

    public function AllRequestsTypesWithClientCheck($clientType, $paginationCount)
    {
        $data = ContactRequest::where('client_type', $clientType)->orderBy('id', 'DESC')
            ->paginate($paginationCount);
        foreach ($data as $dt) {
            $dt->data = json_decode($dt->data, true);
            if ($dt->data['phone'] !== null) {
                $check = substr($dt->data['phone'], 0, 4);
                if ((string)$check === '+966') {
                    $phone = str_replace('+966', '0', $dt->data['phone']);
                    $client = Client::where('phone', $phone)->orWhere('second_phone', $phone)->first();
                    if ($client != null) {
                        $dt->client_exist = true;
                    } else {
                        $dt->client_exist = false;
                    }
                } elseif (substr($dt->data['phone'], 0, 3) === '966' )
                {
                    $phone = str_replace('966', '0', $dt->data['phone']);
                    $client = Client::where('phone', $phone)->orWhere('second_phone', $phone)->first();
                    if ($client != null) {
                        $dt->client_exist = true;
                    } else {
                        $dt->client_exist = false;
                    }
                } elseif (substr($dt->data['phone'], 0, 6) === '00966')
                {
                    $phone = str_replace('00966', '', $dt->data['phone']);
                    $client = Client::where('phone', $phone)->orWhere('second_phone', $phone)->first();
                    if ($client != null) {
                        $dt->client_exist = true;
                    } else {
                        $dt->client_exist = false;
                    }
                } elseif (substr($dt->data['phone'], 0, 1) === '5')
                {
                    $phone = '0' . $dt->data['phone'];
                    $client = Client::where('phone', $phone)->orWhere('second_phone', $phone)->first();
                    if ($client != null) {
                        $dt->client_exist = true;
                    } else {
                        $dt->client_exist = false;
                    }
                } else {
                    $phone = $dt->data['phone'];
                    $client = Client::where('phone', $phone)->orWhere('second_phone', $phone)->first();
                    if ($client != null) {
                        $dt->client_exist = true;
                    } else {
                        $dt->client_exist = false;
                    }
                }
            }
        }
        return Response(['data' => $data]);
    }
}
