from smartcard.System import readers
from smartcard.util import toHexString
from smartcard.Exceptions import NoCardException
import requests
import time

# Target URL of your Laravel API
API_URL = "http://127.0.0.1:8000/api/nfc/tap"

def get_uid(connection):
    # Standard ACR122U command to get the Card UID
    COMMAND = [0xFF, 0xCA, 0x00, 0x00, 0x00]
    data, sw1, sw2 = connection.transmit(COMMAND)
    if sw1 == 0x90 and sw2 == 0x00:
        return "".join([f"{x:02X}" for x in data])
    return None

def main():
    r = readers()
    if not r:
        print("[!] No ACR122U reader detected. Please plug it into a USB port.")
        return

    reader = r[0]
    print(f"[✓] Connected to: {reader}")
    print(">>> SYSTEM READY: You can now tap an NFC Card...\n")

    last_uid = None

    while True:
        try:
            connection = reader.createConnection()
            connection.connect()
            uid = get_uid(connection)

            # If a new card is detected
            if uid and uid != last_uid:
                print(f"[+] Card Tapped! UID: {uid}")
                last_uid = uid
                
                # Send the UID to the Laravel backend
                try:
                    res = requests.post(API_URL, json={'uid': uid})
                    print(f"    [✓] Successfully sent to Laravel! (Status: {res.status_code})")
                except requests.exceptions.RequestException as e:
                    print(f"    [x] Connection Error: Please make sure 'php artisan serve' is running.")

        except NoCardException:
            # Reset when the card is removed
            last_uid = None
        except Exception as e:
            pass
        
        # Polling delay
        time.sleep(0.5)

if __name__ == '__main__':
    main()
