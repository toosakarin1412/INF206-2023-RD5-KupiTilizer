<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Coupon;
use App\Models\MyCoupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use \Carbon\Carbon;

class CouponController extends Controller
{

    /**
     * Menampilkan halaman manajemen admin.
     *
     * 
     */
    public function index(){
        $coupons=DB::table('coupons')->get();
        return view('admincoupon',['coupons'=>$coupons]);
    }

    public function addCoupon(Request $request): RedirectResponse
    {
        $request->validate([
            'nama_coupon' => ['required', 'string', 'max:255'],
            'poin' => ['required', 'integer'],
            'deskripsi' => ['string', 'required']
            //'masa_berlaku' => ['string', 'required','max:255']
            // 'gambar_coupon' => ['string', 'nullable']
    ]);

        $date = Carbon::now();
        $Coupon = Coupon::create([
            'id'=>(string)$date->format('ymd').bin2hex(random_bytes(3)),
            'nama_coupon' => $request->nama_coupon,
            'poin' => $request->poin,
            'deskripsi' => $request->deskripsi,
            //'masa_berlaku' => $request->masa_berlaku,
            //'gambar_coupon' => $request->gambar_coupon
    ]);

    return redirect('admin/coupon')->with('success', 'Coupon berhasil ditambahkan!');
    }

    public function delete($id): RedirectResponse
    {

        //menghapus akun dari database dimana id sesuai dengan parameter
        DB::table('coupons')->where('id', $id)->delete();
        
        ///kembali ke laman manage coupon dengan alert succes
        if(Auth::user()->role == "admin"){
            return redirect('admin/coupon')->with('success', 'Coupon berhasil dihapus!');
        }else{
            return redirect('manager/coupon')->with('success', 'Coupon berhasil dihapus!');
        }
    }

    /**
     * Menampilkan halaman edit coupon
     *
     * @param  string $id
     * @return \Illuminate\View\View
     */
    public function show($id): View
    {   $coupons=DB::table('coupons')->where('id', $id)->get();
        return view('editcoupon',['coupons'=>$coupons]);
    }

    public function update(Request $request, $id): RedirectResponse
    {   

        //aturan untuk inputan data
        $rules = [
            'nama_coupon' => ['required', 'string', 'max:255'],
            'poin' => ['required', 'integer'],
            'deskripsi' => ['string', 'required']
        ];

        //validasi inputan dengan rules
        $request->validate($rules);

        //melakukan update data
        DB::table('coupons')->where('id', $id)
            ->update([
            'nama_coupon' => $request->nama_coupon,
            'poin' => $request->poin,
            'deskripsi' => $request->deskripsi,
            ]);        
        
        //kembali ke laman coupon dengan alert succes
        if(Auth::user()->role == "admin"){
            return redirect('/admin/coupon')->with('success', 'Data coupon berhasil diedit!');
        }else{
            return redirect('/manager/coupon')->with('success', 'Data coupon berhasil diedit!');
        }
    }

    public function halamanKupon(){
        $coupons=DB::table('coupons')->get();
        return view('coupon',['coupons'=>$coupons]);
    }

    public function MyCoupon(){
        $coupons=DB::table('my_coupons')->join('coupons', 'coupons.id', '=', 'my_coupons.coupon_id')->get();
        return view('mycoupon',['coupons'=>$coupons]);
    }

    public function redeem(Request $request){

        $poinUser = DB::table('users')->where('id', Auth::user()->id)->get(['poin']);
        //dd($poinUser);
        $poinPakai = DB::table('coupons')->where('id', $request->kodeCoupon)->get(['poin']);
        // dd($poinPakai);

        if($poinUser[0]->poin - $poinPakai[0]->poin < 0){
            return redirect()->back()->with("failed","Coupon gagal ditukarkan, point and kurang");
        }

        DB::beginTransaction();
        try{
            MyCoupon::create([
                'user_id'=>Auth::user()->id,
                'coupon_id' => $request->kodeCoupon
            ]);
    
            DB::table('users')->where('id', Auth::user()->id)
                ->update([
                'poin' => $poinUser[0]->poin - $poinPakai[0]->poin
                ]);  
            DB::commit();
        }catch(Exception $e){
            DB::rollback();
            return redirect()->back()->with("failed","Coupon gagal ditukarkan");
        }

        return redirect()->back()->with("success","Coupon berhasil ditukarkan");
    }

};