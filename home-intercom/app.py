from flask import Flask, render_template, jsonify
from flask_sock import Sock
import json
import threading

app = Flask(__name__)
sock = Sock(app)

rooms = {
    "1": {"name": "اتاق", "online": False},
    "2": {"name": "پذیرایی", "online": False},
}
clients = {}
lock = threading.Lock()

@app.route("/")
def index():
    return render_template("index.html")

@app.route("/status")
def status():
    with lock:
        return jsonify(rooms)

@sock.route("/ws/<room>")
def websocket(ws, room):
    if room not in rooms:
        ws.close()
        return

    with lock:
        clients[room] = ws
        rooms[room]["online"] = True

    other = "2" if room == "1" else "1"
    with lock:
        other_ws = clients.get(other)
    if other_ws:
        try:
            other_ws.send(json.dumps({"type": "presence", "room": room, "online": True}))
        except Exception:
            pass

    try:
        while True:
            raw = ws.receive()
            if raw is None:
                break
            try:
                message = json.loads(raw)
            except (TypeError, json.JSONDecodeError):
                continue

            target = message.get("to")
            if target not in rooms or target == room:
                continue
            message["from"] = room
            with lock:
                target_ws = clients.get(target)
            if target_ws:
                try:
                    target_ws.send(json.dumps(message))
                except Exception:
                    pass
    finally:
        with lock:
            if clients.get(room) is ws:
                clients.pop(room, None)
                rooms[room]["online"] = False
            other_ws = clients.get(other)
        if other_ws:
            try:
                other_ws.send(json.dumps({"type": "presence", "room": room, "online": False}))
            except Exception:
                pass

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=False)
