# -*- coding: utf-8 -*-
from voip.config import BaseConfig
import time
import datetime
import threading
import sys
import re
import serial
import serial.tools.list_ports
import linecache
import coloredlogs
import logging
import requests
import argparse

active_ports = []
active_devices = {}
trusted_device_list = []
#call_schedule_list = {}
#call_errors_list = {}
#call_actions_list = {}

parser = argparse.ArgumentParser()
parser.add_argument(
	'-d', '--debug',
	help='Print lots of debugging statements',
	action='store_const', dest='loglevel', const=logging.DEBUG,
	default=logging.WARNING,
)
parser.add_argument(
	'-v', '--verbose',
	help='Be verbose',
	action='store_const', dest='loglevel', const=logging.INFO,
)
args = parser.parse_args()

logging.basicConfig(format="%(asctime)s [%(threadName)-12.12s] [%(levelname)-5.5s]  %(message)s", handlers=[
	logging.FileHandler("{0}/{1}.log".format('logs', datetime.datetime.now().strftime(BaseConfig.APP_DATE_FORMAT))),
	logging.StreamHandler()
])

logger = logging.getLogger()
coloredlogs.install(level=args.loglevel)


def print_exception_code(usb_port=None):
	exc_type, exc_obj, tb = sys.exc_info()
	f = tb.tb_frame
	line_number = tb.tb_lineno
	filename = f.f_code.co_filename
	linecache.checkcache(filename)
	line = linecache.getline(filename, line_number, f.f_globals)
	logger.critical('[{}] EXCEPTION IN ({}, LINE {} "{}"): {}'.format(usb_port, filename, line_number, line.strip(), exc_obj))


class VOIPSerial:
	def add_device(self, phone, port_path):
		if phone:
			phone_status = self.get_phone_stats(phone)

			if phone_status:
				phone_carrier = phone_status['carrier']
				if not phone_carrier:
					logger.warning('Carrier not set on phone - {}'.format(phone))
					phone_carrier = 'NO-POOL-CARRIER'


				phone_number = int(phone)

				active_devices[phone_number] = {
					'active':        False,
					'number':        phone,
					'carrier':       phone_carrier,
					'call_active':   False,
					'call_incoming': False,
					'call_outgoing': False,
					'call_phone':    None,
					'call_id':       None,
				}

				logger.info('[SYS][ADDED DEVICE][{}][{}][{}][{}]'.format(phone_carrier, len(active_devices), port_path, phone_number))
				trusted_device_list.append(phone_number)

				return phone_number
			else:
				logger.error('Could not find number in database, please check if list is up to date')
		else:
			logger.warning('Could not add device - {}'.format(port_path))

	@staticmethod
	def get_phone_stats(phone):

		result = None

		post_data = {'phone_number': phone, 'server_key': BaseConfig.SERVER_KEY}
		response = requests.post(BaseConfig.API_URL + 'get_number_stats', data=post_data)

		if response.status_code == 200:
			api_response = response.json()
			if api_response['status'] == 'success':
				result = api_response['data']
			if api_response['status'] == 'invalid_server_key':
				logger.error('API Connection refused, invalid SERVER_KEY')
			else:
				logger.debug('API Connection status [get_phone_stats] ({}) - Phone : {}, '.format(api_response['status'], phone))
		else:
			logger.error('API Connection Failed [get_phone_stats] ({}) - Phone : {} '.format(response.status_code, phone))

		return result

	@staticmethod
	def get_schedule_list(phone=None):

		result = None
		# result = False

		# now = datetime.datetime.now()
		# now_plus_time_range = now + datetime.timedelta(minutes=BaseConfig.SCHEDULE_TIME_INTERVAL)

		post_data = {
			# 'from':         now.strftime(BaseConfig.APP_DATE_FORMAT),
			# 'to':           now_plus_time_range.strftime(BaseConfig.APP_DATE_FORMAT),
			'phone_number': phone,
			'server_key':   BaseConfig.SERVER_KEY
		}
		# response = requests.post(BaseConfig.API_URL + 'get_schedule_list_range', data=post_data, timeout=5, verify=False)
		response = requests.post(BaseConfig.API_URL + 'get_schedule_list', data=post_data, timeout=5)

		if response.status_code == 200:
			api_response = response.json()
			if api_response['status'] == 'success':
				# result = True
				#call_schedule_list[phone] = api_response['data']
				return api_response['data']
			if api_response['status'] == 'invalid_server_key':
				logger.error('API Connection refused, invalid SERVER_KEY')
			else:
				logger.debug('API Connection status [get_schedule_list] ({}) - Phone : {}, '.format(api_response['status'], phone))
		else:
			logger.error('API Connection Failed [get_schedule_list] ({}) - Phone : {} '.format(response.status_code, phone))

		logger.debug('API Connection result [get_schedule_list] Phone : {} | Result : {} '.format(phone, result))

		return result

	def get_last_record(self, phone_number):
		result = {}

		check_query = self.get_schedule_list(phone_number)

		if check_query:
			call_start_string = datetime.datetime.strptime(check_query['call_start'], BaseConfig.APP_DATE_FORMAT)
			call_end_string = datetime.datetime.strptime(check_query['call_end'], BaseConfig.APP_DATE_FORMAT)
			call_now_string = datetime.datetime.now().strftime(BaseConfig.APP_DATE_FORMAT)

			if check_query['call_status'] == 0 and call_now_string > str(call_start_string):
				result = {'status': 'call_start', 'call_phone': check_query['call_phone'], 'call_id': check_query['id']}
			//TODO::VV just enter time when to call
			//import datetime
			//print(
			//	datetime.datetime.fromtimestamp(
			//		int("1284101485")
			//	).strftime('%Y-%m-%d %H:%M:%S')
			//)*/

		return result

	def parse_sim_number(self, response, port_path):
		if len(response.replace(" ", "")) > 0 and '+CNUM' in response:
			try:
				phone_answer_parse = response.split('"')[3]
				return self.add_device(phone_answer_parse, port_path)
			except IndexError:
				logger.warning("Parse SIM number IndexError : {}".format(response))
				print_exception_code()
				raise Exception
		else:
			print_exception_code()
			logger.warning("Parse SIM number Exception : {}".format(response))
			raise Exception

	@staticmethod
	def device_incoming_call(phone_number, call_id=None):
		device_object = active_devices[phone_number]
		device_object['active'] = True
		device_object['call_incoming'] = True
		if call_id:
			device_object['call_id'] = call_id

	def device_outgoing_call(self, phone_number, call_id, call_number):
		device_object = active_devices[phone_number]
		device_object['active'] = True
		device_object['call_outgoing'] = True
		device_object['call_id'] = call_id
		device_object['call_phone'] = call_number
		self._insert_call_action(phone_number, 'call_start', call_id)

	def device_hang_up_call(self, phone_number):
		device_object = active_devices[phone_number]
		self._insert_call_action(phone_number, 'call_hang_up', device_object['call_id'])
		device_object['active'] = False
		device_object['call_outgoing'] = False
		device_object['call_incoming'] = False
		device_object['call_phone'] = None
		device_object['call_id'] = None

	def device_call_ended(self, phone_number):
		# TODO:: check if other calling number exists to assign ID
		device_object = active_devices[phone_number]
		self._insert_call_action(phone_number, 'call_ended', device_object['call_id'])
		device_object['active'] = False
		device_object['call_outgoing'] = False
		device_object['call_incoming'] = False
		device_object['call_phone'] = None
		device_object['call_id'] = None

	def device_call_answered(self, phone_number):
		device_object = active_devices[phone_number]
		device_object['active'] = True
		self._insert_call_action(phone_number, 'call_incoming_answered', 0, device_object['call_phone'])

	def device_call_initiated(self, phone_number):
		self._insert_call_action(phone_number, 'call_incoming_initiated')

	def device_call_incoming(self, phone_number, number=None, trusted=False):
		device_object = active_devices[phone_number]
		if trusted:
			logger.info("Incoming trusted call on port: {}".format(phone_number))
			device_object['active'] = True
			device_object['call_incoming'] = True
			device_object['call_phone'] = number
			self._insert_call_action(phone_number, 'call_incoming_trusted', 0, number)
			self.device_incoming_call(phone_number)
		else:
			caller_number = device_object['number']
			self._insert_call_action(phone_number, 'call_incoming_unknown', 0, caller_number)

	@staticmethod
	def _insert_call_action(phone_number, action, call_id=0, incoming_number=''):
		now_at = datetime.datetime.now().strftime(BaseConfig.APP_DATE_FORMAT)

		device_object = active_devices[phone_number]
		device_call_call_number = device_object['call_phone']

		if call_id == 0:
			call_id = device_object['call_id']

		variables = {
			'call_id':         call_id,
			'action':          action,
			'now_at':          now_at,
			'phone_number':    phone_number,
			'call_number':     device_call_call_number,
			'incoming_number': incoming_number,
		}

		if action == 'call_ended' and call_id != 0:
			variables.update({'call_type': 'call_reconnect'})
        #VV call_reconnect not used

		logger.debug("Variables: {}".format(variables))

		variables.update({'server_key': BaseConfig.SERVER_KEY})

		response = requests.post(BaseConfig.API_URL + 'call_log', data=variables)

		if response.status_code == 200:
			api_response = response.json()
			if api_response['status'] != 'success':
				logger.debug('API Connection status [insert_call_action] ({}) - Phone : {}, '.format(api_response['status'], phone_number))
			if api_response['status'] == 'invalid_server_key':
				logger.error('API Connection refused, invalid SERVER_KEY')
		else:
			logger.error('API Connection Failed [insert_call_action] ({}) - Phone : {} '.format(response.status_code, phone_number))


def modem_serial_init(ser, port_path, voip):
	ser.write('AT^U2DIAG=0\r'.encode('ascii'))
	time.sleep(0.1)
	ser.write('AT+CHUP\r'.encode('ascii'))
	time.sleep(0.1)
	ser.write('AT+CLIP=1\r'.encode('ascii'))
	time.sleep(0.1)
	retries = 0
	retries_repeat = 2
	while retries < BaseConfig.MODEM_RECONNECT_RETRY_COUNT:

		time.sleep(retries_repeat)
		ser.write('AT+CNUM\r'.encode('ascii'))
		time.sleep(0.2)
		phone_number = ser.read(size=256)
		phone_answer = phone_number.decode('utf-8')

		# noinspection PyBroadException
		try:
			return voip.parse_sim_number(phone_answer, port_path)

		except Exception:
			retries += 1
			retries_repeat += 1
			print_exception_code()
			logger.warning("Failed to read port {}, trying again #{}".format(port_path, retries))
			# logger.warning("Failed to read port response {}".format(phone_answer))
			time.sleep(retries_repeat)


def modem_serial(port_path):
	voip = VOIPSerial()

	ser = serial.Serial(
		port=port_path,
		baudrate=19200,
		timeout=0,
		rtscts=True,
		dsrdtr=True,
		xonxoff=True,
	)

	modem_phone_number = None

	# noinspection PyBroadException
	try:
		ser.close()
		time.sleep(0.5)
		ser.open()
		modem_phone_number = modem_serial_init(ser, port_path, voip)
	except IOError:  # if port is already opened, close it and open it again and print message
		ser.close()
		time.sleep(0.5)
		ser.open()
		modem_phone_number = modem_serial_init(ser, port_path, voip)
		logger.critical("Port was already open, was closed and opened again! [{}]".format(port_path))
	except Exception as e:
		logger.critical("Modem Serial Exception : {}".format(port_path))
		print_exception_code(port_path)

	if ser.is_open and modem_phone_number and int(modem_phone_number) in trusted_device_list:
		# noinspection PyBroadException
		try:
			while True:
				c = ser.read(size=1024)
				logger.debug("Reading port : [{}] [{}]".format(port_path, modem_phone_number))
				port_check = voip.get_last_record(modem_phone_number)

				if port_check:
					port_attributes = active_devices[modem_phone_number]

					if port_check['status'] != 'call_active':
						logger.debug("Port check: {}".format(port_check))

					if port_check['status'] == 'call_start' and not port_attributes['active']:
						ser.write('AT+CHUP\r'.encode('ascii'))
						time.sleep(0.5)
						call_command = 'ATD{};\r'.format(port_check['call_phone'])
						ser.write(call_command.encode('ascii'))
						logger.info('[SLAVE][CALLING][{}][{}]'.format(modem_phone_number, port_check['call_phone']))
						voip.device_outgoing_call(modem_phone_number, port_check['call_id'], port_check['call_phone'])

					if port_check['status'] == 'call_hangup' and port_attributes['active']:
						ser.write('AT+CHUP\r'.encode('ascii'))
						voip.device_hang_up_call(modem_phone_number)

				if len(c) > 0:
					answer = c.decode('utf-8')

					if "CONN" in answer:
						voip.device_call_answered(modem_phone_number)

					if "ORIG" in answer:
						voip.device_call_initiated(modem_phone_number)

					if "CLIP" in answer:
						try:
							caller_regex = re.findall('^\+CLIP:\s*"(\+{0,1}\d+)".*$', answer, re.MULTILINE)
							caller_number = caller_regex[0]
							caller_trusted = False
							if int(caller_number) in trusted_device_list:
								ser.write('ATA\r'.encode('ascii'))
								caller_trusted = True
							voip.device_call_incoming(modem_phone_number, caller_number, caller_trusted)
						except IndexError:
							logger.warning("Prob. modem just booting")

					if "CEND" in answer:
						#voip.device_call_ended(modem_phone_number)
						voip.device_hang_up_call(modem_phone_number)

				time.sleep(BaseConfig.MODEM_SLEEP_SECONDS)
		except Exception:
			print_exception_code(port_path)
			logger.critical("Trying to re-start thread : {}".format(port_path))
			ser.close()
			start_thread(port_path)
	else:
		logger.error("Could not open Serial port {} and phone {}".format(port_path, modem_phone_number))
		ser.close()


def start_thread(port_path):
	t = threading.Thread(target=modem_serial, args=(port_path,))
	t.daemon = True
	t.start()
	time.sleep(6)


# noinspection PyBroadException
try:
	logger.info("Starting VOIP Server - RGK")

	trusted_device_list.append(37122411141)

	connected = []
	ports = serial.tools.list_ports.comports()

	p = 0
	for element in sorted(ports):
		p += 1
		if (p % 3) == 0:
			if sys.platform == 'darwin':
				if "HUAWEI" in element.device:
					connected.append(element.device)
			else:
				connected.append(element.device)

	logger.info("Connected COM ports ({}): {} ".format(len(connected), str(connected)))

	for port in connected:
		start_thread(port)

except Exception:
	logger.critical("Error: Unable to start thread")
	print_exception_code()

try:
	while True:
		pass
except KeyboardInterrupt:
	# foreach list and insert ID
	exit()
