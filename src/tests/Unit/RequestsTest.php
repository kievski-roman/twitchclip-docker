<?php

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use App\Http\Requests\UpdateVttRequest;
use App\Http\Requests\UpdateStyleRequest;

class RequestsTest extends TestCase
{
public function test_update_vtt_request_rules()
{
// Правильные данные
$good = [
'vtt'   => 'WEBVTT...',
'style' => ['color'=>'#112233','fontSize'=>20]
];

$r     = new UpdateVttRequest();
$rules = $r->rules();

$v = Validator::make($good, $rules);
$this->assertTrue($v->passes(), 'Valid data should pass');

// Неправильные данные
$bad = [
'vtt'   => '',
'style' => ['color'=>'zzz','fontSize'=>5]
];
$v2 = Validator::make($bad, $rules);
$this->assertTrue($v2->fails(), 'Invalid data should fail');
$this->assertArrayHasKey('vtt',       $v2->errors()->messages());
$this->assertArrayHasKey('style.color',$v2->errors()->messages());
}

public function test_update_style_request_rules()
{
$good = ['style'=>[
'color'=>'#abcdef','fontSize'=>30,
'outline'=>'#000000','fontStyle'=>'italic'
]];
$r     = new UpdateStyleRequest();
$rules = $r->rules();

$v = Validator::make($good, $rules);
$this->assertTrue($v->passes());

$bad = ['style'=>[
'color'=>'nope','fontSize'=>100,'fontStyle'=>'huge'
]];
$v2 = Validator::make($bad, $rules);
$this->assertTrue($v2->fails());
$this->assertArrayHasKey('style.color',    $v2->errors()->messages());
$this->assertArrayHasKey('style.fontSize', $v2->errors()->messages());
$this->assertArrayHasKey('style.fontStyle',$v2->errors()->messages());
}
}
