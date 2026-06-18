import cv2
import requests
import threading
import time

API_URL = "http://192.168.10.44:6700/face-verification"
DELAY = 2.0

employee_id = input(
    "Masukkan Nama / NIK Karyawan: "
).strip()

print("1. Input diterima:", employee_id)
print("2. Import OpenCV OK")

recognized_success = False
similarity_score = 0

searching = False
last_call = 0


def verify_face(frame):
    global recognized_success
    global similarity_score
    global searching

    try:

        success, encoded = cv2.imencode(
            ".jpg",
            frame
        )

        if not success:
            print("Gagal encode gambar")
            return

        files = {
            "file": (
                "capture.jpg",
                encoded.tobytes(),
                "image/jpeg"
            )
        }

        print(
            f"[{time.strftime('%H:%M:%S')}] "
            f"Verifikasi {employee_id}"
        )

        resp = requests.post(
            API_URL,
            params={
                "name": employee_id
            },
            files=files,
            timeout=5
        )

        print(
            f"[{time.strftime('%H:%M:%S')}] "
            f"HTTP {resp.status_code}"
        )

        if resp.status_code != 200:
            print(resp.text)
            return

        result = resp.json()

        print(result)

        similarity_score = (
            result.get("result", {})
                  .get("similarity", 0)
        )

        status = (
            result.get("result", {})
                  .get("status", False)
        )

        if status:

            recognized_success = True

            print(
                f"[{time.strftime('%H:%M:%S')}] "
                f"VERIFIED "
                f"(similarity={similarity_score}%)"
            )

        else:

            print(
                f"[{time.strftime('%H:%M:%S')}] "
                f"FAILED "
                f"(similarity={similarity_score}%)"
            )

    except Exception as e:

        print("ERROR:", e)

    finally:

        searching = False

print("3. Akan membuka kamera")
print("=== FACE VERIFICATION ===")

cap = cv2.VideoCapture(
    0,
    cv2.CAP_DSHOW
)

print("4. Objek kamera dibuat")

if not cap.isOpened():
    print("5. Kamera GAGAL dibuka")
    input("Tekan Enter...")
    exit()

print("5. Kamera BERHASIL dibuka")

while True:

    ret, frame = cap.read()

    if not ret:
        break

    now = time.time()

    if (
        not searching
        and not recognized_success
        and now - last_call >= DELAY
    ):

        searching = True
        last_call = now

        threading.Thread(
            target=verify_face,
            args=(frame.copy(),),
            daemon=True
        ).start()

    cv2.putText(
        frame,
        f"ID: {employee_id}",
        (10, 30),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.7,
        (255, 255, 255),
        2
    )

    cv2.putText(
        frame,
        f"Similarity: {similarity_score}%",
        (10, 60),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.7,
        (0, 255, 255),
        2
    )

    status_text = (
        "SEARCHING..."
        if searching
        else "READY"
    )

    cv2.putText(
        frame,
        status_text,
        (10, 90),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.7,
        (255, 255, 0),
        2
    )

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
            employee_id,
            (40, 280),
            cv2.FONT_HERSHEY_SIMPLEX,
            1.2,
            (255, 255, 255),
            3
        )

        cv2.putText(
            frame,
            f"Similarity: {similarity_score}%",
            (40, 330),
            cv2.FONT_HERSHEY_SIMPLEX,
            1,
            (255, 255, 255),
            2
        )

        cv2.imshow(
            "Face Verification",
            frame
        )

        cv2.waitKey(3000)

        break

    cv2.imshow(
        "Face Verification",
        frame
    )

    key = cv2.waitKey(1) & 0xFF

    if key == 27 or key == ord("q"):
        break

cap.release()
cv2.destroyAllWindows()