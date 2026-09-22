import { useEffect, useState } from "react";
import { Link, useLocation, useParams } from "react-router-dom";
import { getOrganizerEvents } from "../api/organizers";
import { publishEvent } from "../api/events";

export default function OrganizerEventsPage() {
  const { organizerId } = useParams();
  const { state } = useLocation();
  const organizer = state?.organizer;
  const [events, setEvents] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [actionError, setActionError] = useState(null);
  const [publishingId, setPublishingId] = useState(null);

  useEffect(() => {
    const loadEvents = async () => {
      try {
        const response = await getOrganizerEvents(organizerId);
        setEvents(response.data);
      } catch {
        setError("Nie udało się pobrać wydarzeń. Spróbuj ponownie za chwilę.");
      } finally {
        setIsLoading(false);
      }
    };

    loadEvents();
  }, [organizerId]);

  const handlePublish = async (eventId) => {
    setActionError(null);
    setPublishingId(eventId);

    try {
      const updatedEvent = await publishEvent(eventId);

      setEvents((currentEvents) =>
        currentEvents.map((event) =>
          event.id === eventId ? { ...event, ...updatedEvent } : event,
        ),
      );
    } catch {
      setActionError(
        "Nie udalo sie opublikowac wydarzenia. Sprobuj ponownie za chwile.",
      );
    } finally {
      setPublishingId(null);
    }
  };

  const statusLabels = {
    draft: "Szkic",
    published: "Opublikowane",
    cancelled: "Anulowane",
  };

  return (
    <section>
      <Link className="back-link" to="/my-organizers">
        &larr; Powrót do listy organizatorów
      </Link>
      <p className="eyebrow">Organizator</p>
      <h1>
        {organizer
          ? `Wydarzenia: ${organizer.name}`
          : "Wydarzenia organizatora"}
      </h1>

      <Link
        className="button button-primary"
        to={`/organizers/${organizerId}/events/new`}
        state={{ organizer }}
      >
        {" "}
        Utwórz nowe wydarzenie
      </Link>

      {isLoading && <p>Ładowanie wydarzeń...</p>}
      {error && <p role="alert">{error}</p>}
      {actionError && (
        <p className="form-message form-message--error" role="alert">
          {actionError}
        </p>
      )}
      {!isLoading && !error && events.length === 0 && (
        <p>Ta organizacja nie ma jeszcze żadnych wydarzeń.</p>
      )}

      <div className="organizer-events-list">
        {events.map((event) => (
          <article className="organizer-event-card" key={event.id}>
            <div>
              <p className="organizer-event-card__date">
                {new Intl.DateTimeFormat("pl-PL", {
                  day: "numeric",
                  month: "long",
                  year: "numeric",
                }).format(new Date(event.starts_at))}
              </p>
              <h2>{event.title}</h2>
              <p>
                {new Intl.DateTimeFormat("pl-PL", {
                  hour: "2-digit",
                  minute: "2-digit",
                }).format(new Date(event.starts_at))}
                {"–"}
                {new Intl.DateTimeFormat("pl-PL", {
                  hour: "2-digit",
                  minute: "2-digit",
                }).format(new Date(event.ends_at))}
              </p>
            </div>

            <div className="organizer-event-card__meta">
              <span className={`status-badge status-badge--${event.status}`}>
                {statusLabels[event.status] ?? event.status}
              </span>
              <span>{event.applications_count} zgłoszeń</span>
              {event.status === "draft" && (
                <button
                  className="button button-primary organizer-event-card__publish"
                  type="button"
                  onClick={() => handlePublish(event.id)}
                  disabled={publishingId === event.id}
                >
                  {publishingId === event.id ? "Publikowanie..." : "Opublikuj"}
                </button>
              )}
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}
