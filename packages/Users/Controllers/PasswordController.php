<?php
namespace PhpSoft\Users\Controllers;

use Mail;
use Auth;
use Validator;
use Illuminate\Mail\Message;
use Illuminate\Http\Request;
use PhpSoft\Users\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Foundation\Auth\ResetsPasswords;

class PasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * Forgot password
     * 
     * @param  Request $request 
     * @return json
     */
    public function forgot(Request $request)
    {
        $validator = Validator::make($request->only('email'), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $response = Password::sendResetLink($request->only('email'), function (Message $message) {

            $message->subject($this->getEmailSubject());
        });

        if ($response == Password::INVALID_USER) {
            return response()->json('User is invalid.', 400);
        }

        return response()->json(null, 200);
    }

    /**
     * Reset password
     * 
     * @param  Request $request
     * @return json
     */
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');

        $response = Password::reset($credentials, function ($user, $password) {

            $this->resetPassword($user, $password); // @codeCoverageIgnore
        });

        switch ($response) {
            case Password::PASSWORD_RESET:
                return response()->json(null, 200);

            default:
                return response()->json(null, 400);
        }
    }

    /**
     * Change password
     *
     * @param  Request $request
     * @return Response
     */
    public function changePassword(Request $request)
    {
        if (!$this->checkAuth()) {
            return response()->json(null, 401);
        }

        $validator = Validator::make($request->all(), [
            'old_password' => 'required|min:6',
            'password'     => 'required|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(arrayView('phpsoft.users::errors/validation', [
                'errors' => $validator->errors()
            ]), 400);
        }

        $user = Auth::user();
        $checkPassword = Auth::attempt(['id' => $user->id, 'password' => $request['old_password']]);
        if (!$checkPassword) {
            return response()->json(arrayView('phpsoft.users::errors/validation', [
                'errors' => ['Old password is incorrect.']
            ]), 401);
        }

        $change = $user->changePassword($request['password']);

        if (!$change) {
            return response()->json(null, 500); // @codeCoverageIgnore
        }

        return response()->json(null, 204);
    }
}
