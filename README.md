## About The App

1. All customers and services apis are protected using Bearer Authentication.

- First call `POST /auth/register` to create a new superuser and get the token.
- Send `Authorization` header to other apis and its value should be `Bearer <token>`.


2. All GET requests are cached using `Redis`. Cache keys and tags are invalidated automatically on any action applied to each resource such as create, update or delete.


3. Docker configurations are added just run the following to be up and running:

```bash
docker-compose up --build

# 4 containers created : mysql, redis, nginx and app
# script will automatically execute migration and seeders files.
```

4. Swagger documentation is served here:

```
http://localhost:8000/api/documentation
```
