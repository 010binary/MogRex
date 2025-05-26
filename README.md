# 💸 Laravel Concurrent Transaction API

This is a lightweight RESTful Laravel API that processes concurrent credit/debit transactions using Laravel queues and SQLite as the database. It ensures data integrity via locking and runs entirely in a Docker container.

---

## ⚙️ Features

- ✅ RESTful transaction creation (`POST /transactions`)
- 🧠 Concurrent-safe processing via queues
- 🔐 Authentication with Laravel Sanctum
- 🗃️ SQLite database (included in repo)
- 🐳 Dockerized and deployable to Render

---

## 📁 Prerequisites

- Docker installed
- GitHub account for deployment
- Render account (<https://render.com>)

---

## 🚀 Setup & Usage

### 🔧 1. Clone the Repository

```bash
git clone https://github.com/your-username/laravel-concurrent-api.git
cd laravel-concurrent-api
