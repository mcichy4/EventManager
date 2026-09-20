import { useState } from "react";
import { login } from "../api/auth";

export default function LoginForm({ onLogin }) {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setIsSubmitting(true);
    setError(null);

    try {
      const user = await login({ email, password });
      onLogin(user);
    } catch {
      setError("Nieprawidłowy email lub hasło");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <h1>Logowanie</h1>
      <label>
        Email:
        <input
          type="email"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          required
        />
      </label>
      <label>
        Hasło:
        <input
          type="password"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          required
        />
      </label>
      {error && <p role="alert">{error}</p>}
      <button type="submit" disabled={isSubmitting}>
        {isSubmitting ? "Logowanie..." : "Zaloguj się"}
      </button>
    </form>
  );
}
