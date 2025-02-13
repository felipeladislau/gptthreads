# 🤖 GPTThreads - Chatbot Assistant

GPTThreads is a chatbot assistant that leverages OpenAI's ChatGPT API, using threads and custom assistants to create a more structured and interactive conversation flow. This project is built with PHP and JavaScript (without Node.js) and includes features like syntax highlighting and a copy button for code snippets.

## 📌 Features

- **Threads & Custom Assistants**: Organize interactions into structured threads with specialized assistants.
- **ChatGPT API Integration**: Powered by OpenAI's ChatGPT for intelligent responses.
- **Code Highlighting & Copy Button**: Prism.js is used to format and highlight code blocks, with a built-in copy button for convenience.
- **Simple PHP Backend**: Handles requests and responses efficiently.
- **Lightweight Frontend**: Built with vanilla JavaScript and HTML.

## 📂 Project Structure

```
/gptthreads
├── threads/        # Folder for managing thread data
├── chat.php        # PHP backend for handling chat interactions
├── index.html      # Main UI for the chatbot
├── script.js       # Frontend logic (message handling, API requests)
├── style.css       # Basic styling for the chat interface
```

## 🚀 Getting Started

### 1️⃣ Clone the Repository

```sh
git clone https://github.com/felipeladislau/gptthreads.git
cd gptthreads
```

### 2️⃣ Configure OpenAI API

Edit `chat.php` to include your OpenAI API key:

```php
$apiKey = 'your_openai_api_key_here';
```

### 3️⃣ Start a Local Server

Use PHP's built-in server:

```sh
php -S localhost:8000
```

Then open `http://localhost:8000` in your browser.

## 📜 Documentation

- **OpenAI API**: [https://platform.openai.com/docs](https://platform.openai.com/docs)
- **Prism.js (Syntax Highlighting)**: [https://prismjs.com](https://prismjs.com)
- **Project Repository**: [https://github.com/felipeladislau/gptthreads](https://github.com/felipeladislau/gptthreads)

## 🛠️ Future Improvements

- Implement local storage for chat history.
- Add UI enhancements for better user experience.
- Improve thread handling for complex interactions.

## 📄 License

This project is licensed under a **non-commercial license**, meaning it can be used for personal and educational purposes, but not for commercial use. See `LICENSE` for details.

---

Developed by **Felipe Ladislau** with GPT help 🚀

