import { useState } from "react";
import { Link, useLocation, useNavigate, useParams } from "react-router-dom";
import { createEvent } from "../api/organizers";

export default function CreateEventPage() {
  const { organizerId } = useParams();
  const { state } = useLocation();
  const organizer = state?.organizer;
  const navigate = useNavigate();

  const [formData, setFormData] = useState({
    title: "",
    description: "",
    category: "other",
    starts_at: "",
    ends_at: "",
    application_deadline: "",
    location: "",
    address: "",
    participant_limit: "",
  });

  const [error, setError] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleChange = (e) => {
    const { name, value } = e.target;

    setFormData((current) => ({
      ...current,
      [name]: value,
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setIsSubmitting(true);

    try {
      await createEvent(organizerId, {
        ...formData,
        description: formData.description.trim() || null,
        address: formData.address.trim() || null,
        application_deadline: formData.application_deadline || null,
        participant_limit: formData.participant_limit
          ? Number(formData.participant_limit)
          : null,
      });
      navigate(`/organizers/${organizerId}/events`, {
        state: { organizer },
      });
    } catch {
      setError(
        "Nie udalo sie utworzyc wydarzenia. Sprawdz daty i wymagane pola.",
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <section className="create-event-page">
      <Link
        className="back-link"
        to={`/organizers/${organizerId}/events`}
        state={{ organizer }}
      >
        &larr; Wydarzenia organizatora
      </Link>

      <form className="event-form" onSubmit={handleSubmit}>
        <div>
          <p className="eyebrow">Nowe wydarzenie</p>
          <h1>
            {organizer
              ? `Utworz wydarzenie dla: ${organizer.name}`
              : "Utworz wydarzenie"}
          </h1>
        </div>

        <label>
          Tytul wydarzenia
          <input
            name="title"
            type="text"
            value={formData.title}
            onChange={handleChange}
            maxLength="255"
            required
          />
        </label>

        <label>
          Kategoria
          <select
            name="category"
            value={formData.category}
            onChange={handleChange}
          >
            <option value="other">Inne</option>
            <option value="workshops">Warsztaty</option>
            <option value="sport">Sport</option>
            <option value="music">Muzyka</option>
            <option value="networking">Networking</option>
            <option value="education">Edukacja</option>
          </select>
        </label>

        <label>
          Opis <span>(opcjonalnie)</span>
          <textarea
            name="description"
            value={formData.description}
            onChange={handleChange}
            rows="5"
          />
        </label>

        <div className="event-form__dates">
          <label>
            Data i godzina rozpoczecia
            <input
              name="starts_at"
              type="datetime-local"
              value={formData.starts_at}
              onChange={handleChange}
              required
            />
          </label>

          <label>
            Data i godzina zakonczenia
            <input
              name="ends_at"
              type="datetime-local"
              value={formData.ends_at}
              onChange={handleChange}
              required
            />
          </label>
        </div>

        <label>
          Termin zgloszen <span>(opcjonalnie)</span>
          <input
            name="application_deadline"
            type="datetime-local"
            value={formData.application_deadline}
            onChange={handleChange}
          />
        </label>

        <label>
          Miejsce
          <input
            name="location"
            type="text"
            value={formData.location}
            onChange={handleChange}
            maxLength="255"
            required
          />
        </label>

        <label>
          Adres <span>(opcjonalnie)</span>
          <input
            name="address"
            type="text"
            value={formData.address}
            onChange={handleChange}
            maxLength="255"
          />
        </label>

        <label>
          Limit uczestnikow <span>(opcjonalnie)</span>
          <input
            name="participant_limit"
            type="number"
            min="1"
            value={formData.participant_limit}
            onChange={handleChange}
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
          {isSubmitting ? "Tworzenie wydarzenia..." : "Utworz wydarzenie"}
        </button>
      </form>
    </section>
  );
}
