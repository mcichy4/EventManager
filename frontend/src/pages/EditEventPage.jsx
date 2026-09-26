import { useState } from "react";
import { Link, useLocation, useNavigate, useParams } from "react-router-dom";
import { updateEvent } from "../api/events";

const categories = {
  other: "Inne",
  workshops: "Warsztaty",
  sport: "Sport",
  music: "Muzyka",
  networking: "Networking",
  education: "Edukacja",
};

const toDateTimeLocal = (value) => (value ? value.slice(0, 16) : "");

export default function EditEventPage() {
  const { eventId, organizerId } = useParams();
  const { state } = useLocation();
  const navigate = useNavigate();
  const organizer = state?.organizer;
  const event = state?.event;
  const [formData, setFormData] = useState(() => ({
    title: event?.title ?? "",
    description: event?.description ?? "",
    category: event?.category ?? "other",
    starts_at: toDateTimeLocal(event?.starts_at),
    ends_at: toDateTimeLocal(event?.ends_at),
    application_deadline: toDateTimeLocal(event?.application_deadline),
    location: event?.location ?? "",
    address: event?.address ?? "",
    participant_limit: event?.participant_limit ?? "",
  }));
  const [error, setError] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  if (!event) {
    return (
      <section>
        <Link className="back-link" to={`/organizers/${organizerId}/events`}>
          ← Wydarzenia organizatora
        </Link>
        <p role="alert">
          Wróć do listy wydarzeń, aby otworzyć edycję wybranego wydarzenia.
        </p>
      </section>
    );
  }

  const handleChange = (inputEvent) => {
    const { name, value } = inputEvent.target;
    setFormData((current) => ({ ...current, [name]: value }));
  };

  const handleSubmit = async (submitEvent) => {
    submitEvent.preventDefault();
    setError(null);
    setIsSubmitting(true);

    try {
      await updateEvent(eventId, {
        ...formData,
        description: formData.description.trim() || null,
        address: formData.address.trim() || null,
        application_deadline: formData.application_deadline || null,
        participant_limit:
          formData.participant_limit === ""
            ? null
            : Number(formData.participant_limit),
      });
      navigate(`/organizers/${organizerId}/events`, { state: { organizer } });
    } catch {
      setError("Nie udało się zapisać zmian. Sprawdź dane i spróbuj ponownie.");
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
        ← Wydarzenia organizatora
      </Link>
      <form className="event-form" onSubmit={handleSubmit}>
        <div>
          <p className="eyebrow">Edycja wydarzenia</p>
          <h1>{event.title}</h1>
        </div>
        <label>
          Tytuł wydarzenia
          <input
            name="title"
            value={formData.title}
            onChange={handleChange}
            required
            maxLength="255"
          />
        </label>
        <label>
          Kategoria
          <select
            name="category"
            value={formData.category}
            onChange={handleChange}
          >
            {Object.entries(categories).map(([value, label]) => (
              <option key={value} value={value}>
                {label}
              </option>
            ))}
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
            Data i godzina rozpoczęcia
            <input
              name="starts_at"
              type="datetime-local"
              value={formData.starts_at}
              onChange={handleChange}
              required
            />
          </label>
          <label>
            Data i godzina zakończenia
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
          Termin zgłoszeń <span>(opcjonalnie)</span>
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
            value={formData.location}
            onChange={handleChange}
            required
            maxLength="255"
          />
        </label>
        <label>
          Adres <span>(opcjonalnie)</span>
          <input
            name="address"
            value={formData.address}
            onChange={handleChange}
            maxLength="255"
          />
        </label>
        <label>
          Limit uczestników <span>(opcjonalnie)</span>
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
          {isSubmitting ? "Zapisywanie..." : "Zapisz zmiany"}
        </button>
      </form>
    </section>
  );
}
