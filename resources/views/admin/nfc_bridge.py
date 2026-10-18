import ctypes
from ctypes import wintypes
import time
import json
import requests  # Ginagamit na natin ang requests para malinis at walang BOM error
import pyperclip

winscard = ctypes.windll.winscard

SCARD_SCOPE_USER = 0
SCARD_SHARE_SHARED = 2
SCARD_PROTOCOL_T0 = 1
SCARD_PROTOCOL_T1 = 2
SCARD_LEAVE_CARD = 0
SCARD_S_SUCCESS = 0

class SCARD_IO_REQUEST(ctypes.Structure):
    _fields_ = [
        ("dwProtocol", wintypes.DWORD),
        ("cbPciLength", wintypes.DWORD),
    ]

GET_UID_COMMAND = (ctypes.c_ubyte * 5)(0xFF, 0xCA, 0x00, 0x00, 0x00)
LARAVEL_API_URL = "http://127.0.0.1:8000/api/nfc/tap"

def get_readers(hContext):
    pcchReaders = wintypes.DWORD()
    ret = winscard.SCardListReadersA(hContext, None, None, ctypes.byref(pcchReaders))
    if ret != SCARD_S_SUCCESS or pcchReaders.value == 0:
        return []
    
    mszReaders = ctypes.create_string_buffer(pcchReaders.value)
    ret = winscard.SCardListReadersA(hContext, None, mszReaders, ctypes.byref(pcchReaders))
    if ret != SCARD_S_SUCCESS:
        return []
    
    readers_raw = mszReaders.raw.decode('latin1').split('\x00')
    return [r for r in readers_raw if r]

def send_to_laravel(uid):
    try:
        response = requests.post(
            LARAVEL_API_URL,
            json={"card_uid": uid},
            headers={"Content-Type": "application/json", "Accept": "application/json"},
            timeout=3
        )
        
        if response.status_code == 200:
            data = response.json()
            print(f"    -> [LARAVEL OK]: {data.get('message', 'Card tap received')}")
        else:
            print(f"    -> [Server Error]: Status Code {response.status_code}")
            
    except requests.exceptions.RequestException as e:
        print(f"    -> [Connection Error]: {e}")

def main():
    print("==================================================")
    print("        SIATRACK ACR122U Smart Bridge Online        ")
    print("==================================================")

    hContext = wintypes.ULONG()
    if winscard.SCardEstablishContext(SCARD_SCOPE_USER, None, None, ctypes.byref(hContext)) != SCARD_S_SUCCESS:
        print("[!] Windows Smart Card Service is not running.")
        return

    readers_list = get_readers(hContext)
    if not readers_list:
        print("[!] Walang na-detect na ACR122U Reader.")
        return

    reader_name = readers_list[0]
    print(f"[*] Reader: {reader_name}")
    print("[READY] Place an NFC card on the ACR122U reader...\n")

    last_uid = None
    last_tap_time = 0

    while True:
        hCard = wintypes.ULONG()
        dwActiveProtocol = wintypes.DWORD()

        ret = winscard.SCardConnectA(
            hContext,
            reader_name.encode('latin1'),
            SCARD_SHARE_SHARED,
            SCARD_PROTOCOL_T0 | SCARD_PROTOCOL_T1,
            ctypes.byref(hCard),
            ctypes.byref(dwActiveProtocol)
        )

        if ret == SCARD_S_SUCCESS:
            io_send = SCARD_IO_REQUEST(dwActiveProtocol.value, ctypes.sizeof(SCARD_IO_REQUEST))
            pbRecv = (ctypes.c_ubyte * 256)()
            pcbRecvLength = wintypes.DWORD(256)

            ret_trans = winscard.SCardTransmit(
                hCard,
                ctypes.byref(io_send),
                GET_UID_COMMAND,
                5,
                None,
                pbRecv,
                ctypes.byref(pcbRecvLength)
            )

            if ret_trans == SCARD_S_SUCCESS and pcbRecvLength.value >= 2:
                resp = list(pbRecv[:pcbRecvLength.value])
                if resp[-2] == 0x90 and resp[-1] == 0x00:
                    card_uid = "".join([f"{b:02X}" for b in resp[:-2]])
                    now = time.time()

                    if card_uid != last_uid or (now - last_tap_time) > 2.0:
                        last_uid = card_uid
                        last_tap_time = now

                        print(f"\n[CARD TAP DETECTED] UID: {card_uid}")
                        pyperclip.copy(card_uid)
                        send_to_laravel(card_uid)

            winscard.SCardDisconnect(hCard, SCARD_LEAVE_CARD)

        time.sleep(0.2)

if __name__ == "__main__":
    main()