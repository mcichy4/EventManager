import { useState } from "react";
import LoginForm from "./components/LoginForm";
import "./App.css";

function App() {
  const [user, setUser] = useState(null);
  if (user) {
    return (
      <main>
        <h1>Witaj, {user.name}!</h1>
        <p>Jesteś zalogowany jako {user.email}</p>
      </main>
    );
  }

  return (
    <main>
      <LoginForm onLogin={setUser} />
    </main>
  );
}

export default App;
