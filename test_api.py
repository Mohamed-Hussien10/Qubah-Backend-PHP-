import requests

url = "http://localhost:8000/api/v1/free-trial/stages"

response = requests.get(url)
print(response.json())
