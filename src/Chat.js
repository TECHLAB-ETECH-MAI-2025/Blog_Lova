import React, { useEffect, useState } from 'react';
import { getMessages, sendMessage } from './api';
import 'bootstrap/dist/css/bootstrap.min.css';


function Chat({ receiverId }) {
  const [messages, setMessages] = useState([]);
  const [content, setContent] = useState('');

  useEffect(() => {
    fetchMessages();
  }, [receiverId]); // Recharger quand receiverId change

  const fetchMessages = async () => {
    try {
      const data = await getMessages(receiverId);
      setMessages(data);
    } catch (error) {
      console.error('Erreur lors de la récupération des messages:', error);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!content.trim()) return;

    try {
      const newMessage = await sendMessage(receiverId, content);
      setMessages((prev) => [...prev, newMessage]);
      setContent('');
    } catch (error) {
      console.error('Erreur lors de l\'envoi du message:', error);
    }
  };

  return (
    <div className="container my-4" style={{ maxWidth: '600px' }}>
      <h2 className="mb-4">Discussion avec l'utilisateur {receiverId}</h2>

      <div className="border rounded p-3 mb-3" style={{ height: '300px', overflowY: 'auto', backgroundColor: '#f8f9fa' }}>
        {messages.length === 0 && <p className="text-muted">Aucun message pour le moment.</p>}
        {messages.map((msg) => (
          <div key={msg.id} className="mb-3">
            <div>
              <strong>{msg.sender_name}:</strong> {msg.content}
            </div>
            <small className="text-muted">{msg.createdAt}</small>
          </div>
        ))}
      </div>

      <form onSubmit={handleSubmit} className="d-flex">
        <input
          type="text"
          className="form-control"
          value={content}
          onChange={(e) => setContent(e.target.value)}
          placeholder="Écris ton message..."
          aria-label="Message"
        />
        <button type="submit" className="btn btn-primary ms-2">
          Envoyer
        </button>
      </form>
    </div>
  );
}

export default Chat;
