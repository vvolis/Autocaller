#!/usr/bin/python3

import sys
import time
import serial

try:
    script_type = sys.argv[1]
    if script_type == 'modem':

        try:
            modem_function = sys.argv[2]
            modem_port = sys.argv[3]

            if modem_function == 'call':
                phone = serial.Serial(modem_port,  9600, timeout=5)
                try:
                    phone.write('AT+CHUP\r'.encode())
                    time.sleep(0.5)
                    command = "ATD{};\r".format(sys.argv[4]);
                    phone.write(command.encode())
                    print('CALL_OK');
                except SerialException:
                    print('CALL_ERROR');
                finally:
                    phone.close()
                    
            if modem_function == 'recall':
                phone = serial.Serial(modem_port,  9600, timeout=5)
                try:
                    command = "ATD{};\r".format(sys.argv[4]);
                    phone.write(command.encode())
                    answer = phone.read(32);
                    print(answer.decode('utf-8'));
                except SerialException:
                    print('RECALL_ERROR');
                finally:
                    phone.close()

            if modem_function == 'hangup':  
                phone = serial.Serial(modem_port,  9600, timeout=5)
                try:
                    phone.write(('AT+CHUP\r').encode('ascii'))
                    print('HANGUP_OK');
                except SerialException:
                    print('HANGUP_ERROR');
                finally:
                    phone.close()

            if modem_function == 'get_number':  
                phone = serial.Serial(modem_port,  9600, timeout=5)
                try:
                    phone.write(('AT+CNUM\r').encode('ascii'))
                    phoneNumber = phone.read(128);
                    print(phoneNumber.decode('utf-8'));
                except SerialException:
                    print('GET_NUMBER_ERROR');
                finally:
                    phone.close()

            if modem_function == 'set_number':
                print('set_number??')
                phone = serial.Serial(modem_port,  9600, timeout=5)
                try:
                    print('set_number_start')
                    phone.write(('AT+CPBS="ON"\r').encode('ascii'))
                    time.sleep(0.5)
                    phone.write(('AT+CPBW=,"{}"\r').format(sys.argv[4]).encode('ascii'))
                    phoneNumber = phone.read(64);
                    print(phoneNumber.decode('utf-8'));
                    print('SET_NUMBER')
                except SerialException:
                    print('CALL_ERROR');
                finally:
                    phone.close()
                    
        except IndexError:
            print('call/hangup/get_number/set_number')
            
except IndexError:
    print('modem/scripts');