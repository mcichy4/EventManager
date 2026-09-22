import { useState } from "react";
import { useAuth } from "../auth/useAuth";

export default function RegisterForm({ onRegister }) {
  const { register } = useAuth();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [error, setError] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setError(null);

    if (password !== confirmPassword) {
      setError("Hasła nie są zgodne");
      return;
    }

    setIsSubmitting(true);

    try {
      const user = await register({
        name,
        email,
        password,
        passwordConfirmation: confirmPassword,
      });
      onRegister(user);
    } catch {
      setError("Nieprawidłowe dane rejestracyjne");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form className="auth-form" onSubmit={handleSubmit}>
      <h1>Rejestracja</h1>
      <label>
        Nazwa:
        <input
          autoComplete="name"
          type="text"
          value={name}
          onChange={(event) => setName(event.target.value)}
          required
        />
      </label>

      <label>
        Email:
        <input
          autoComplete="email"
          type="email"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          required
        />
      </label>

      <label>
        Hasło:
        <input
          autoComplete="new-password"
          type="password"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          required
        />
      </label>

      <label>
        Powtórz hasło:
        <input
          autoComplete="new-password"
          type="password"
          value={confirmPassword}
          onChange={(event) => setConfirmPassword(event.target.value)}
          required
        />
      </label>

      {error && <p role="alert">{error}</p>}

      <button
        className="button button-primary"
        type="submit"
        disabled={isSubmitting}
      >
        {isSubmitting ? "Rejestracja..." : "Zarejestruj się"}
      </button>
    </form>
  );
}
