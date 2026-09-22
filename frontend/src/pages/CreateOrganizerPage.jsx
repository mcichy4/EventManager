import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { createOrganizer } from "../api/organizers";

export default function CreateOrganizerPage() {
  const navigate = useNavigate();
  const [name, setName] = useState("");
  const [type, setType] = useState("individual");
  const [description, setDescription] = useState("");
  const [error, setError] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setError(null);
    setIsSubmitting(true);

    try {
      await createOrganizer({
        name,
        type,
        description: description.trim() || null,
      });
      navigate("/my-organizers");
    } catch {
      setError(
        "Nie udało się utworzyć organizatora. Sprawdź dane i spróbuj ponownie.",
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <section className="create-organizer-page">
      <Link className="back-link" to="/my-organizers">
        &larr; Moi organizatorzy
      </Link>

      <form className="auth-form" onSubmit={handleSubmit}>
        <div>
          <p className="eyebrow">Nowa organizacja</p>
          <h1>Utwórz organizację</h1>
        </div>

        <label>
          Nazwa
          <input
            type="text"
            value={name}
            onChange={(e) => setName(e.target.value)}
            required
            maxLength="255"
          />
        </label>

        <label>
          Typ organizacji
          <select value={type} onChange={(e) => setType(e.target.value)}>
            <option value="individual">Osoba indywidualna</option>
            <option value="company">Firma / Organizacja</option>
          </select>
        </label>

        <label>
          Opis <span>(opcjonalnie)</span>
          <textarea
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            maxLength="255"
            rows="4"
          />
        </label>

        {error && (
          <p className="form-message form-message--error" role="alert">
            {error}
          </p>
        )}

        <button
          className="button button-primary"
          type="submit"
          disabled={isSubmitting}
        >
          {isSubmitting ? "Tworzenie..." : "Utwórz organizację"}
        </button>
      </form>
    </section>
  );
}
