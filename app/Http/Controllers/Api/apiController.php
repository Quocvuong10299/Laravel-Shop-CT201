<?php

namespace App\Http\Controllers\Api;

use App\Category;
use App\Category_gender;
use App\Color;
use App\Comment;
use App\Http\Controllers\Controller;
use App\Order;
use App\Order_detail;
use App\Product;
use App\Size;
use App\Slide;
use App\Supplier;
use Illuminate\Http\Request;
use App\User;
use Auth;
use Response;
use DB;
use Storage;
use function App\Helper\Helper\toSlug;
class apiController extends Controller
{
//    public function user_index(){
//        return view('admin.admin');
//    }
    public function getUser(){
        $user =  User::orderBy('user_id','desc')->where('user_role',0)->paginate(6);
        return response()->json($user);
    }
    public function getUserToDay(){
        $current_day = date('Y-m-d');
        $member_today = DB::table('users')->where('user_register_date',$current_day)->get();
        return response()->json($member_today);
    }
    public function removeUser($id){
        $delUser = User::find($id);
        $delUser->delete();
        return response()->json(['message'=>'delete success']);
    }
    public function logOutAdmin(){
        Auth::logout();
//        return redirect()->route('getAdminLogin');
        return response()->json(['message'=>'logout success']);
    }
//    category
    public function getCategory(){
        $category =  Category::where('parent_id','>',0)->orderBy('category_id','DESC')->paginate(6);
        $category_parent =  Category::where('parent_id',0)->get();
//        return response()->json($category);
        return Response::json(array(
            'category' => $category,
            'category_parent' => $category_parent,
        ));
    }
    public function addCategory(Request $request){
        $add_cat = new Category;
        $add_cat->category_name = $request->get('name');
        $add_cat->category_slug = toSlug($request->get('name'));
        $add_cat->parent_id = $request->get('items');
        $add_cat->category_gender_id = $request->get('gender');
        $add_cat->category_show = $request->get('show');
        $add_cat->save();
        return $add_cat;
    }
    public function editCategory(Request $request, $id){
        $edit_cat = Category::where('category_id', $id)->first();
        $edit_cat->category_name = $request->get('val_1');
        $edit_cat->category_slug = toSlug($request->get('val_1'));
        $edit_cat->parent_id = $request->get('val_2');
        $edit_cat->category_gender_id = $request->get('val_3');
        $edit_cat->category_show = $request->get('val_4');
        $edit_cat->save();
        return $edit_cat;
    }

//    products
    public function getProduct(){
        $products = DB::table('products')
            ->join('category','products.category_id','=','category.category_id')
            ->join('category_gender','products.category_gender_id','=','category_gender.category_gender_id')
            ->join('supplier','products.supplier_id','=','supplier.supplier_id')
            ->select([
                'category.category_name',
                'products.product_id',
                'products.product_name',
                'products.product_active',
                'products.product_image',
                'products.product_description',
                'products.product_active',
                'products.product_new',
                'products.product_show',
                'category.category_id',
                'category_gender.category_gender_id',
                'category_gender.category_gender_name',
                'supplier.supplier_name',
                'supplier.supplier_id',
            ])
            ->orderBy('product_id','DESC')
            ->paginate(6);
        $product_category = Category::where('parent_id','>',0)->get();
        $product_supplier = Supplier::all();
        $product_gender = Category_gender::all();
        return Response::json(array(
            'products' => $products,
            'category' => $product_category,
            'supplier' => $product_supplier,
            'gender' => $product_gender,
        ));
    }
    public function getDetailProduct($id){
        $product_detail = DB::table('products')
            ->join('category','products.category_id','=','category.category_id')
            ->join('category_gender','products.category_gender_id','=','category_gender.category_gender_id')
            ->join('supplier','products.supplier_id','=','supplier.supplier_id')
            ->select([
                'category.category_name',
                'products.product_id',
                'products.product_name',
                'products.product_active',
                'products.product_image',
                'products.product_description',
                'products.created_at',
                'products.product_active',
                'products.product_new',
                'products.product_show',
                'category.category_id',
                'category_gender.category_gender_id',
                'category_gender.category_gender_name',
                'supplier.supplier_name',
                'supplier.supplier_id',
            ])
            ->orderBy('product_id','DESC')
            ->where('products.product_id', $id)
            ->get();
        return response()->json($product_detail);
    }
    public function addProduct(){

    }

//    comments
    public function getComment(){
        $comments = DB::table('comments')
            ->join('users','comments.user_id','=','users.user_id')
            ->join('products','comments.product_id','=','products.product_id')
            ->select([
                'users.user_name',
                'products.product_name',
                'products.product_id',
                'comments.comment_content',
                'comments.comment_id',
                'comments.created_at',
            ])
            ->orderBy('created_at','DESC')
            ->paginate(5);
        return response()->json($comments);

    }
    public function removeComment($id){
        $delCmt = Comment::find($id);
        $delCmt->delete();
        return response()->json(['message'=>'delete success']);
    }
//    slides
    public function getSlide(){
        $slides = Slide::orderBy('slide_id','DESC')->paginate(2);
        return response()->json($slides);
    }
    public function editSlides(Request $request, $id){
        $edit_slide = Slide::where('slide_id', $id)->first();
        $edit_slide->slide_show = $request->get('val_show');
        $edit_slide->save();
        return $edit_slide;
    }
    public function addSlides(Request $request){
        $image = $request->get('image');

        $image_slide = new Slide;
        $image_slide->slide_link = $this->saveImgBase64($image, 'uploads');
        $image_slide->save();
        return response()->json(['success' => 'You have successfully uploaded an image'], 200);
    }
    protected function saveImgBase64($param, $folder)
    {
        list($extension, $content) = explode(';', $param);
        $tmpExtension = explode('/', $extension);
        preg_match('/.([0-9]+) /', microtime(), $m);
        $fileName = sprintf('img%s%s.%s', date('YmdHis'), $m[1], $tmpExtension[1]);
        $content = explode(',', $content)[1];
        $storage = Storage::disk('public');

        $checkDirectory = $storage->exists($folder);

        if (!$checkDirectory) {
            $storage->makeDirectory($folder);
        }

        $storage->put($folder . '/' . $fileName, base64_decode($content), 'public');

        return $fileName;
    }

//    color
    public function getColor(){
        $colors = DB::table('colors')->get();
        return response()->json($colors);
    }
    public function addColor(Request $request){
        $color = new Color;
        $color->color_value = $request->get('color');
        $color->color_name = $request->get('color_name');
        $color->save();
        return response()->json(['message'=>'create success']);
    }
//    size
    public function getSize(){
        $size = DB::table('sizes')->get();
        return response()->json($size);
    }
    public function addSize(Request $request){
        $add_size = new Size;
        $add_size->size_value = $request->get('size');
        $add_size->save();
        return response()->json(['message'=>'create success']);
    }
//    supplier
    public function getSupplier(){
        $suppliers = Supplier::all();
        return response()->json($suppliers);
    }
    public function postSupplier(Request $request){
        $supp = new Supplier;
        $supp->supplier_name = $request->get('name_supplier');
        $supp->save();
        return response()->json(['message'=>'created success']);
    }
//    order
    public function getAllOrder(){
        $all_order = Order::orderBy('order_date','DESC')->paginate(20);
        return response()->json($all_order);
    }
    public function getDetailOrder($id){
        $order_detail = DB::table('order_detail')
            ->join('orders','order_detail.order_id','=','orders.order_id')
            ->join('products','order_detail.product_id','=','products.product_id')
            ->select([
                'orders.*',
                'order_detail.*'
            ])
            ->where('orders.order_id',$id)
            ->get();
        return response()->json($order_detail);
    }
    public function stateStatus(Request $request,$id){
        $new_state = Order::findOrFail($id);
        $new_state->order_state = $request->get('val_state');
        $new_state->save();
    }
    public function getOrderToday(){
        $current_day = date('Y-m-d');
        $bill_today = DB::table('orders')->whereDate('order_date',$current_day)->get();
        return response()->json($bill_today);
    }
    public function getRevenueMonth(){
        $year = date('Y');
        $revenueMonth_1 = DB::table('orders')->orderBy('order_date','desc')->whereMonth('order_date','=',1)->whereYear('order_date','=',$year)->get(['order_total']);
        $revenueMonth_2 = DB::table('orders')->orderBy('order_date','desc')->whereMonth('order_date','=',2)->whereYear('order_date','=',$year)->get(['order_total']);
        $revenueMonth_3 = DB::table('orders')->orderBy('order_date','desc')->whereMonth('order_date','=',3)->whereYear('order_date','=',$year)->get(['order_total']);
        $revenueMonth_4 = DB::table('orders')->orderBy('order_date','desc')->whereMonth('order_date','=',4)->whereYear('order_date','=',$year)->get(['order_total']);
        $revenueMonth_5 = DB::table('orders')->orderBy('order_date','desc')->whereMonth('order_date','=',5)->whereYear('order_date','=',$year)->get(['order_total']);
        $revenueMonth_6 = DB::table('orders')->orderBy('order_date','desc')->whereMonth('order_date','=',6)->whereYear('order_date','=',$year)->get(['order_total']);
        $revenueMonth_7 = DB::table('orders')->orderBy('order_date','desc')->whereMonth('order_date','=',7)->whereYear('order_date','=',$year)->get(['order_total']);
        $revenueMonth_8 = DB::table('orders')->orderBy('order_date','desc')->whereMonth('order_date','=',8)->whereYear('order_date','=',$year)->get(['order_total']);
        $revenueMonth_9 = DB::table('orders')->orderBy('order_date','desc')->whereMonth('order_date','=',9)->whereYear('order_date','=',$year)->get(['order_total']);
        $revenueMonth_10 = DB::table('orders')->orderBy('order_date','desc')->whereMonth('order_date','=',10)->whereYear('order_date','=',$year)->get(['order_total']);
        $revenueMonth_11 = DB::table('orders')->orderBy('order_date','desc')->whereMonth('order_date','=',11)->whereYear('order_date','=',$year)->get(['order_total']);
        $revenueMonth_12 = DB::table('orders')->orderBy('order_date','desc')->whereMonth('order_date','=',12)->whereYear('order_date','=',$year)->get(['order_total']);
        return Response::json(array(
            'revenueMonth_1' => $revenueMonth_1,
            'revenueMonth_2' => $revenueMonth_2,
            'revenueMonth_3' => $revenueMonth_3,
            'revenueMonth_4' => $revenueMonth_4,
            'revenueMonth_5' => $revenueMonth_5,
            'revenueMonth_6' => $revenueMonth_6,
            'revenueMonth_7' => $revenueMonth_7,
            'revenueMonth_8' => $revenueMonth_8,
            'revenueMonth_9' => $revenueMonth_9,
            'revenueMonth_10' => $revenueMonth_10,
            'revenueMonth_11' => $revenueMonth_11,
            'revenueMonth_12' => $revenueMonth_12,
        ));
    }
    public function getNumberOrder(){
        $count_length = DB::table('orders')->where('order_state',0)->get();
        return response()->json($count_length);
    }
}
