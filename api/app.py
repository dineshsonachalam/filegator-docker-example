from flask_restx import Resource, Api,fields
from flask import Flask, request
import json

app = Flask(__name__)
api = Api(app)

with open('users.json') as f:
    user_data = json.load(f)

resource_fields = api.model('Resource', {
    'username': fields.String,
    'password': fields.String
})






@api.route('/user/validate/')
@api.doc(body=resource_fields)
@api.response(500, 'Unexpected error')
@api.response(400, 'Bad request')
@api.response(401, 'Unauthorized')
@api.response(200, 'Success')
class ValidateLogin(Resource):
    def post(self):
        try:
            data = request.json
            username = data['username']
            password = data['password']
            if user_data.get(username).get("password") == password:
                permissions = user_data.get(username).get("permissions")
                return {'validation': 'success', 'permissions': permissions}
            else:
                return {'validation': 'failure'}
        except Exception as e:
                print("Exception: ",e)
                return {'validation': 'failure'}


if __name__ == '__main__':
    app.run(debug=False,host="0.0.0.0",port=5000)
