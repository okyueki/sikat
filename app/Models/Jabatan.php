<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Jabatan extends Model
{
    use HasFactory;
    protected $connection = 'server_74';
    protected $table = 'jabatan';
    protected $primaryKey = 'kd_jbtn';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['kd_jbtn', 'nama_jbtn'];
}