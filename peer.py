import socket 
import threading

HEADER = 64
PORT = 5050
NODE = socket.gethostbyname(socket.gethostname())
ADDR = (NODE, PORT)

FORMAT = 'utf-8'
DISCONNECT_MESSAGE = "!exit"

def send(msg):
    message = msg.encode(FORMAT)
    msg_length = len(message)
    send_length = str(msg_length).encode(FORMAT)
    send_length += b' ' * (HEADER - len(send_length))
    client.send(send_length)
    client.send(message)
    print(client.recv(2048).decode(FORMAT))

def handle_client(conn, addr):
    print(f"[NEW CONNECTION] {addr} connected.")

    connected = True
    while connected:
        msg_length = conn.recv(HEADER).decode(FORMAT)
        if msg_length:
            msg_length = int(msg_length)
            msg = conn.recv(msg_length).decode(FORMAT)
            if msg == DISCONNECT_MESSAGE:
                connected = False

            print(f"[{addr}] {msg}")
            #input()
            if(msg == 'version'):
                print("Send version")
                conn.send("verack".encode(FORMAT))
                print("Send version acknowledgement")
                conn.send("version".encode(FORMAT))
            elif (msg == 'getaddr'):
                print("Send address")
                conn.send(NODE.encode(FORMAT))
            else:
                print("Send address received")
                conn.send(NODE.encode(FORMAT))
                

    conn.close()

def start():
    node.listen()
    print(f"[LISTENING] Node is listening on {NODE}")
    while True:
        conn, addr = node.accept()
        thread = threading.Thread(target=handle_client, args=(conn, addr))
        thread.start()
        print(f"[ACTIVE CONNECTIONS] {threading.activeCount() -1}")

try:
    client = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    client.connect(ADDR)
    print("Send version")
    send("version")
    #input()
    print("Send version acknowledgement")
    send("verack")
    #input()
    print("Send address")
    send("addr")
    #input()
    print("Send address received")
    send("getaddr")
    #input()
    peerconnected = True
    while peerconnected:
        msg = input("Msg>>")
        send(msg)
        if msg == DISCONNECT_MESSAGE:
            peerconnected = False
except:
    print("No peers")
    node = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    node.bind(ADDR)
    print("[STARTING] Node starting...")
    start()
