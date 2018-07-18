<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\BinaryUuid\HasBinaryUuid;

class AppClient extends Model
{

  use HasBinaryUuid;

  public function getKeyName()
  {
      return 'client_id';
  }

	//32 chars
	public static function generateToken()
	{
			return bin2hex(random_bytes(16));
	}

	public function users()
	{
		return $this->belongsToMany('App\User', 'app_client_user', 'client_id', 'user_id');
	}

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
      'client_id', 'name', 'os', 'dns_name', 'ip_address', 'domain', 'client_version', 'last_check_in', 'last_backup'
  ];

  protected $table = 'app_client';

  protected $dates = ['created_at', 'updated_at', 'last_check_in', 'last_backup'];

}
