import axios from 'axios';

const API_BASE = 'http://localhost:8000/api/chat'; // URL de ton backend Symfony

// Récupérer les messages échangés avec un utilisateur (receiverId)
export const getMessages = async (receiverId) => {
  try {
    const response = await axios.get(`${API_BASE}/messages/${receiverId}`, {
      withCredentials: true, // pour envoyer les cookies (session Symfony)
    });
    return response.data; // liste des messages en JSON
  } catch (error) {
    console.error('Erreur getMessages:', error);
    throw error;
  }
};

// Envoyer un message à un utilisateur (receiverId)
export const sendMessage = async (receiverId, content) => {
  try {
    const response = await axios.post(
      `${API_BASE}/send/${receiverId}`,
      { content },
      {
        withCredentials: true,
        headers: {
          'Content-Type': 'application/json',
        },
      }
    );
    return response.data; // message envoyé en JSON
  } catch (error) {
    console.error('Erreur sendMessage:', error);
    throw error;
  }
  
};
