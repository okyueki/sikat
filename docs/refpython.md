import cv2
import requests
import threading
import time
from collections import deque

API_SEARCH = "http://192.168.10.44:6700/face-search"
DELAY = 2.0
THRESHOLD = 0.9

recognized_name = "Unknown"
recognized_distance = None
recognized_bbox = None

recognized_success = False

searching = False
last_call = 0

logs = deque(maxlen=8)


def add_log(msg):
    timestamp = time.strftime("%H:%M:%S")
    text = f"[{timestamp}] {msg}"

    print(text)
    logs.appendleft(text)


def search_face(frame):
    global recognized_name
    global recognized_distance
    global recognized_bbox
    global recognized_success
    global searching

    try:

        success, encoded = cv2.imencode(".jpg", frame)

        if not success:
            add_log("Gagal encode frame")
            return

        files = {
            "file": (
                "frame.jpg",
                encoded.tobytes(),
                "image/jpeg"
            )
        }

        add_log("Mengirim request ke API")

        resp = requests.post(
            API_SEARCH,
            files=files,
            params={"limit": 1},
            timeout=5
        )

        add_log(f"HTTP {resp.status_code}")

        if resp.status_code != 200:
            add_log(f"ERROR RESPONSE: {resp.text}")
            return

        result = resp.json()

        faces = (
            result.get("result", {})
                .get("similar_faces", [])
        )

        if len(faces) > 0:

            item = faces[0]

            name = item.get(
                "name",
                "Unknown"
            )

            similarity = float(
                item.get(
                    "similarity",
                    0
                )
            )

            if similarity >= 80:

                recognized_name = name
                recognized_success = True

                add_log(
                    f"DIKENALI: {name} "
                    f"(similarity={similarity:.2f}%)"
                )

            else:

                recognized_name = "Unknown"

                add_log(
                    f"Similarity rendah "
                    f"({similarity:.2f}%)"
                )

        else:

            recognized_name = "Unknown"

            add_log("Tidak ada kecocokan")

    except Exception as e:

        add_log(f"ERROR: {e}")

    finally:

        searching = False


print("=== REALTIME FACE SEARCH ===")

cap = cv2.VideoCapture(0)

if not cap.isOpened():
    print("ERROR membuka kamera")
    exit()

while True:

    ret, frame = cap.read()

    if not ret:
        break

    now = time.time()

    if (
        not searching and
        not recognized_success and
        now - last_call >= DELAY
    ):

        searching = True
        last_call = now

        threading.Thread(
            target=search_face,
            args=(frame.copy(),),
            daemon=True
        ).start()

    # Bounding box (jika API mendukung)
    if recognized_bbox:

        try:

            x1, y1, x2, y2 = recognized_bbox

            cv2.rectangle(
                frame,
                (int(x1), int(y1)),
                (int(x2), int(y2)),
                (0, 255, 0),
                2
            )

        except:
            pass

    # Nama
    cv2.putText(
        frame,
        f"Name: {recognized_name}",
        (10, 30),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.7,
        (0, 255, 0),
        2
    )

    # Distance
    if recognized_distance is not None:

        cv2.putText(
            frame,
            f"Distance: {recognized_distance:.4f}",
            (10, 60),
            cv2.FONT_HERSHEY_SIMPLEX,
            0.6,
            (0, 255, 255),
            2
        )

    # Status
    status = (
        "SEARCHING..."
        if searching
        else "READY"
    )

    cv2.putText(
        frame,
        status,
        (10, 90),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.6,
        (255, 255, 0),
        2
    )

    # Log terakhir
    cv2.putText(
        frame,
        "LOG:",
        (10, 130),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.5,
        (255, 255, 255),
        1
    )

    y = 150

    for log in list(logs)[:5]:

        cv2.putText(
            frame,
            log[:70],
            (10, y),
            cv2.FONT_HERSHEY_SIMPLEX,
            0.45,
            (220, 220, 220),
            1
        )

        y += 20

    # Jika berhasil dikenali
    if recognized_success:

        overlay = frame.copy()

        cv2.rectangle(
            overlay,
            (0, 0),
            (frame.shape[1], frame.shape[0]),
            (0, 180, 0),
            -1
        )

        frame = cv2.addWeighted(
            overlay,
            0.35,
            frame,
            0.65,
            0
        )

        cv2.putText(
            frame,
            "ACCESS GRANTED",
            (40, 220),
            cv2.FONT_HERSHEY_SIMPLEX,
            1.5,
            (0, 255, 0),
            3
        )

        cv2.putText(
            frame,
            recognized_name,
            (40, 280),
            cv2.FONT_HERSHEY_SIMPLEX,
            1.2,
            (255, 255, 255),
            3
        )

        cv2.imshow(
            "InsightFace Realtime Search",
            frame
        )

        add_log(
            f"Verifikasi berhasil: {recognized_name}"
        )

        print(">>> ACCESS GRANTED")
        print(">>> Program akan ditutup 3 detik lagi")

        cv2.waitKey(3000)

        break

    cv2.imshow(
        "InsightFace Realtime Search",
        frame
    )

    key = cv2.waitKey(1) & 0xFF

    if key == 27 or key == ord("q"):
        break

cap.release()
cv2.destroyAllWindows()