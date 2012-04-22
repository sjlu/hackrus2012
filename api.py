from flask import Flask
from pymongo import Connection
import random
import json

app = Flask(__name__)

db = Connection().hot_or_not

@app.route('/<lang>')
def get_code (lang):
    try:
        num = db.users.find({'files': {'$elemMatch': {'lang': lang}}}).count()
        print num
        rnd = random.randrange(0, num - 1)
        print rnd
        user = db.users.find({'files': {'$elemMatch': {'lang': lang}}})[rnd]
        files_count = len(user['files'])
        rnd = random.randrange(0, files_count - 1)
        the_file = user['files'][rnd]
        the_file['username'] = user['username']
        return json.dumps(the_file)
    except Exception, e:
        print e

@app.route('/<username>/<filename>')
def upvote (username, filename):
    count = db.files.find({'username': username, 'filename': filename}).count()
    print count
    if count == 0:
        db.files.insert(
            {'username': username, 'filename': filename, 'count': 1}
        )
    else:
        db.files.update(
            {'username': username, 'filename': filename},
            {'$inc': {'count': 1}}
        )

    return ''

app.debug = True
app.run(host='0.0.0.0')
