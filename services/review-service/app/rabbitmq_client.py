import pika
import json
import os

RABBITMQ_HOST = os.getenv('RABBITMQ_HOST', 'message-broker')


def publish(queue_name: str, payload: dict):
    try:
        connection = pika.BlockingConnection(pika.ConnectionParameters(host=RABBITMQ_HOST))
        channel = connection.channel()
        channel.queue_declare(queue=queue_name, durable=True)
        body = json.dumps(payload)
        channel.basic_publish(
            exchange='',
            routing_key=queue_name,
            body=body,
            properties=pika.BasicProperties(delivery_mode=2),
        )
        connection.close()
    except Exception as e:
        print(f"RabbitMQ publish error: {e}")
