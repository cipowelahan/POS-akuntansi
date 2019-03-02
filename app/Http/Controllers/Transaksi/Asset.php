<?php

namespace App\Http\Controllers\Transaksi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class Asset extends Controller {

  private $user;

  private $ruleAsset = [
    'nama' => 'required|string',
    'nilai'=> 'required|integer',
    'umur' => 'required|integer',
    'kategori' => 'required|in:tanah,perlengkapan,bangunan,kendaraan,peralatan'
  ];

  private $ruleDaftarAsset = [
    'kategori' => 'string|in:tanah,perlengkapan,bangunan,kendaraan,peralatan'
  ];

  public function __construct(Request $req){
    parent::__construct();
    $this->user = $req->user;
  }

  public function tambah(Request $req) {
    if ($invalid = $this->response->validate($req, $this->ruleAsset)) return $invalid;
    $tanggal = Carbon::now();
    if ($req->kategori == 'tanah' || $req->kategori == 'perlengkapan') {
      $tanggalUmur = NULL;
      $nilaiPenyusutan = 0;
    } else {
      if ($req->umur == 0) return $this->response->messageError('Selain Tanah dan Perlengkapan tidak boleh 0', 403);
      $nilaiPenyusutan = ceil($req->nilai / $req->umur);
      $tanggalUmur = new Carbon($tanggal);
      $tanggalUmur->addYear($req->umur);
    }

    $asset = $this->user->asset()->create([
      'nama' => $req->nama,
      'kategori' => $req->kategori,
      'tanggal' => $tanggal,
      'harga_beli' => $req->nilai,
      'umur_tahun' => $req->umur,
      'nilai_penyusutan' => $nilaiPenyusutan,
      'nilai_sekarang' => $req->nilai,
      'masa_berakhir' => $tanggalUmur
    ]);

    ModulTransaksi::keuangan($this->user, ['asset_id' => $asset->id], 'B', $tanggal, $req->nilai, 'asset');

    return $this->response->data($this->user->asset()->find($asset->id));
  }

  public function daftar(Request $req) {
    if ($invalid = $this->response->validate($req, $this->ruleDaftarAsset)) return $invalid;
    $asset = $this->user->asset()->orderBy('tanggal', 'desc')->when($req->filled('kategori'), function($q) use ($req) {
      $q->where('kategori', $req->kategori);
    })->get();
    return $this->response->data($asset);
  }

}