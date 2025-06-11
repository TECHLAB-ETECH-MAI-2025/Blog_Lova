import React, { useState, useEffect } from 'react';
import Chat from './Chat';

function App() {
  const [receiverId, setReceiverId] = useState(null);
  const [users, setUsers] = useState([]);

  useEffect(() => {
    fetch('http://localhost:8000/api/chat/users', {
      credentials: 'include', // important si tu utilises les sessions/souhaites envoyer les cookies
      headers: {
        'Content-Type': 'application/json',
      }
    })
      .then(res => {
        if (!res.ok) throw new Error('Erreur lors du chargement des utilisateurs');
        return res.json();
      })
      .then(data => {
        setUsers(data);
        if (data.length > 0) {
          setReceiverId(data[0].id); // sélectionner le premier utilisateur par défaut
        }
      })
      .catch(err => console.error(err));
  }, []);

  if (!receiverId) return <div>Chargement des utilisateurs...</div>;

  return (
    <div className="App">
      <h1>front-end react</h1>

      <select onChange={e => setReceiverId(parseInt(e.target.value))} value={receiverId}>
        {users.map(user => (
          <option key={user.id} value={user.id}>
            {user.name}
          </option>
        ))}
      </select>

      <Chat receiverId={receiverId} />
    </div>
  );
}

export default App;
