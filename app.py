#!/usr/bin/env python3
from flask import Flask, Response, redirect
from picamera2 import Picamera2
from picamera2.encoders import MJPEGEncoder
from picamera2.outputs import FileOutput
from picamera2.utils import Transform # type: ignore
import threading, io, signal, sys

# ---- Output buffer for MJPEG frames ----
class StreamingOutput(io.BufferedIOBase):
    def __init__(self):
        super().__init__()
        self.frame = None
        self.cv = threading.Condition()

    def writable(self):
        return True

    def write(self, b):
        with self.cv:
            self.frame = bytes(b)
            self.cv.notify_all()
        return len(b)

    def get(self):
        with self.cv:
            self.cv.wait()
            return self.frame

# ---- Flask app ----
app = Flask(__name__)

# ---- Camera setup ----
picam2 = Picamera2()
VIDEO_WIDTH, VIDEO_HEIGHT, FPS = 1280, 720, 15

# Assume full sensor resolution (adjust if needed for your camera mode)
SENSOR_W, SENSOR_H = 1920, 1080

# Apply 180° rotation at startup
cconfig = picam2.create_video_configuration(
    main={"size": (VIDEO_WIDTH, VIDEO_HEIGHT)},
    transform=Transform(rotation=180)
)
picam2.configure(cconfig)

# ✅ Enable continuous autofocus
picam2.set_controls({"AfMode": 2})

output = StreamingOutput()
encoder = MJPEGEncoder()

# Start recording once, with safe quality fallback
try:
    picam2.start_recording(encoder, FileOutput(output), quality=75)
except (TypeError, KeyError):
    picam2.start_recording(encoder, FileOutput(output))

# ---- Graceful shutdown ----
def shutdown(*_):
    try:
        picam2.stop_recording()
    except Exception:
        pass
    sys.exit(0)

signal.signal(signal.SIGTERM, shutdown)
signal.signal(signal.SIGINT, shutdown)

# ---- HTTP endpoints ----
@app.route("/")
def root():
    return redirect("/video_feed")

def gen():
    boundary = b"--frame\r\n"
    header = b"Content-Type: image/jpeg\r\n\r\n"
    while True:
        frame = output.get()
        if frame:
            yield boundary + header + frame + b"\r\n"

@app.route("/video_feed")
def video_feed():
    return Response(gen(), mimetype="multipart/x-mixed-replace; boundary=frame")


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, threaded=True)