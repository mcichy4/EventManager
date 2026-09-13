import { useEffect, useState } from "react";
import { getCurrentUser, logout } from "./api/auth";
import LoginForm from "./components/LoginForm";
import "./App.css";

function App() {
  const [user, setUser] = useState(null);
  const [isLoadingUser, setIsLoadingUser] = useState(true);
  const [isLoggingOut, setIsLoggingOut] = useState(false);

  useEffect(() => {
    const restoreSession = async () => {
      try {
        const currentUser = await getCurrentUser();
        setUser(currentUser);
      } catch (error) {
        if (error.response?.status !== 401) {
          console.error("Nie udało się przywrócić sesję:", error);
        }
      } finally {
        setIsLoadingUser(false);
      }
    };
    restoreSession();
  }, []);

  const handleLogout = async () => {
    setIsLoggingOut(true);
    try {
      await logout();
      setUser(null);
    } catch (error) {
      console.error("Nie udało się wylogować:", error);
    } finally {
      setIsLoggingOut(false);
    }
  };

  if (isLoadingUser) {
    return (
      <main>
        <p>Ładowanie...</p>
      </main>
    );
  }

  if (user) {
    return (
      <main>
        <h1>Witaj, {user.name}!</h1>
        <p>Jesteś zalogowany jako {user.email}</p>
        <button onClick={handleLogout} disabled={isLoggingOut}>
          {isLoggingOut ? "Wylogowywanie..." : "Wyloguj"}
        </button>
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
