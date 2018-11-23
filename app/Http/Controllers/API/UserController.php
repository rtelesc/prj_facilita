<?php
//namespace App\Http\Controllers\API;
//use Illuminate\Http\Request;
//use App\Http\Controllers\Controller;
//use App\Login;
//use Illuminate\Support\Facades\Auth;
//
//use \Validator;
//class UserController extends Controller
//{
//    public $successStatus = 200;
//    /**
//     * login api
//     *
//     * @return \Illuminate\Http\Response
//     */
//    public function login(){
//        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){
//            $user = Auth::user();
//            return response()->json(['success'], $this-> successStatus);
//        }
//        else{
//            return response()->json(['error'=>'Unauthorised'], 401);
//        }
//    }
//    /**
//     * Register api
//     *
//     * @return \Illuminate\Http\Response
//     */
//    public function register(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'email' => 'required|email',
//            'password' => 'required',
//            'cpf' => 'required',
//
//        ]);
//        if ($validator->fails()) {
//            return response()->json(['error'=>$validator->errors()], 401);
//        }
//        $input = $request->all();
//        $input['password'] = bcrypt($input['password']);
////        $user = Login::create($input);
////        $success['token'] =  $user->createToken('MyApp')-> accessToken;
////        $success['name'] =  $user->name;
//        return response()->json(['success'], $this-> successStatus);
//    }
//    /**
//     * details api
//     *
//     * @return \Illuminate\Http\Response
//     */
//    public function details()
//    {
//        $user = Auth::user();
//        return response()->json(['success' => $user], $this-> successStatus);
//    }
//}