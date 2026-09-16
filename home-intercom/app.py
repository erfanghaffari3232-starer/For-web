from flask import Flask, render_template, jsonify

app = Flask(__name__)

rooms = {
    "1": {"name": "اتاق", "online": False},
    "2": {"name": "پذیرایی", "online": False},
}

@app.route("/")
def index():
    return render_template("index.html")

@app.route("/status")
def status():
    return jsonify(rooms)

@app.route("/online/<room>", methods=["POST"])
def online(room):
    if room in rooms:
        rooms[room]["online"] = True
        return jsonify(ok=True)
    return jsonify(ok=False), 404

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)
