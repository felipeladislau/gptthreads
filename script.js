document.addEventListener("DOMContentLoaded", function() {
  const chatContainer = document.getElementById("chatContainer");
  const chatForm = document.getElementById("chatForm");
  const messageInput = document.getElementById("messageInput");

  // Função para decodificar entidades HTML e caracteres Unicode
  function decodeMessage(text) {
    let decoded = text.replace(/\\u([0-9a-fA-F]{4})/g, (match, code) => {
      return String.fromCharCode(parseInt(code, 16));
    });
    decoded = decoded.replace(/&[a-z0-9]+;/gi, match => {
      const entities = {
        '&amp;': '&',
        '&lt;': '<',
        '&gt;': '>',
        '&#039;': "'",
        '&quot;': '"',
      };
      return entities[match] || match;
    });
    return decoded;
  }

  // Função helper para escapar HTML (importante para segurança)
  function escapeHtml(text) {
    return text.replace(/[&<>"']/g, function(m) {
      switch(m) {
        case '&': return '&amp;';
        case '<': return '&lt;';
        case '>': return '&gt;';
        case '"': return '&quot;';
        case "'": return '&#039;';
        default: return m;
      }
    });
  }

  // Função para destacar blocos de código utilizando PrismJS e adicionar atributo data-copy="true"
  function highlightCodeBlocks(text) {
    const codeBlockRegex = /```(\w*)\n([\s\S]*?)```/g;
    return text.replace(codeBlockRegex, (match, language, code) => {
      const escapedCode = escapeHtml(code);
      return `<pre data-copy="true" class="language-${language} line-numbers"><code>${escapedCode}</code></pre>`;
    });
  }

  // Função para adicionar uma mensagem na interface
  function addMessage(role, text) {
    const decodedText = decodeMessage(text);
    const messageDiv = document.createElement("div");
    messageDiv.classList.add("message", role);
    
    const highlightedText = highlightCodeBlocks(decodedText);
    messageDiv.innerHTML = highlightedText;
    chatContainer.appendChild(messageDiv);
    chatContainer.scrollTop = chatContainer.scrollHeight;
    
    // Atualiza os blocos de código para que o Prism processe e adicione o botão de cópia
    Prism.highlightAllUnder(chatContainer);
  }

  // Carrega a conversa já salva no backend
  function loadConversation() {
    fetch("chat.php")
      .then(response => response.json())
      .then(data => {
        chatContainer.innerHTML = ""; // Limpa a área de chat
        if (data.messages) {
          // Inverte a ordem do array de mensagens
          data.messages.reverse();
          data.messages.forEach(msg => {
            // Extrai o texto correto do JSON
            const text = msg.content[0]?.text?.value || "Mensagem inválida";
            const sender = msg.role === "user" ? "user" : "assistant";
            addMessage(sender, text);
          });
        }
        Prism.highlightAllUnder(chatContainer);
      })
      .catch(error => console.error("Erro ao carregar conversa:", error));
  }

  // Carrega a conversa ao iniciar
  loadConversation();

  // Processa o envio da mensagem pelo usuário
  chatForm.addEventListener("submit", function(e) {
    e.preventDefault();
    const message = messageInput.value.trim();
    if (message === "") return;
    
    // Adiciona imediatamente a mensagem do usuário na interface
    addMessage("user", message);
    messageInput.value = "";
    
    // Envia a mensagem para o backend via POST
    const formData = new FormData();
    formData.append("message", message);
    
    fetch("chat.php", {
      method: "POST",
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      chatContainer.innerHTML = ""; // Limpa e recarrega as mensagens
      if (data.messages) {
        data.messages.forEach(msg => {
          const text = msg.content[0]?.text?.value || "Mensagem inválida";
          const sender = msg.role === "user" ? "user" : "assistant";
          addMessage(sender, text);
        });
      }
      Prism.highlightAllUnder(chatContainer);
    })
    .catch(error => console.error("Erro ao enviar mensagem:", error));
  });
});
