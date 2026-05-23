# MeetlyAI Backend

Laravel REST API backend for MeetlyAI.

---

## Features

### Authentication
- Registration
- Login
- Password Reset
- Sanctum Authentication

### Meetings
- Create Meetings
- Store Transcripts
- Retrieve Meeting Data

### Tasks
- Task Generation
- Task Management

### Team Management
- Team Invitations
- Team Collaboration

### User Profiles
- Profile Updates
- User Settings

### AI Integrations
- Meeting Analysis
- Summary Generation
- AI Assistant Support

---

## Technologies

- Laravel
- PHP
- Sanctum
- SQLite
- MySQL

---

## Installation

composer install

cp .env.example .env

php artisan key:generate

php artisan migrate

php artisan serve
