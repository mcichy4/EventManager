import { useEffect, useState } from "react";
import { Link, useLocation, useParams } from "react-router-dom";
import { getOrganizerEvents } from "../api/organizers";
import { cancelEvent, deleteEvent, publishEvent } from "../api/events";

export default function OrganizerEventsPage() {
  const { organizerId } = useParams();
  const { state } = useLocation();
  const organizer = state?.organizer;
  const [events, setEvents] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [actionError, setActionError] = useState(null);
  const [publishingId, setPublishingId] = useState(null);
  const [processingEventId, setProcessingEventId] = useState(null);

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

  const handleCancel = async (eventId) => {
    if (!window.confirm("Czy na pewno chcesz anulować to wydarzenie?")) {
      return;
    }

    setActionError(null);
    setProcessingEventId(eventId);

    try {
      const updatedEvent = await cancelEvent(eventId);
      setEvents((currentEvents) =>
        currentEvents.map((event) =>
          event.id === eventId ? { ...event, ...updatedEvent } : event,
        ),
      );
    } catch {
      setActionError(
        "Nie udało się anulować wydarzenia. Spróbuj ponownie za chwilę.",
      );
    } finally {
      setProcessingEventId(null);
    }
  };

  const handleDelete = async (eventId) => {
    if (!window.confirm("Czy na pewno chcesz trwale usunąć ten szkic?")) {
      return;
    }

    setActionError(null);
    setProcessingEventId(eventId);

    try {
      await deleteEvent(eventId);
      setEvents((currentEvents) =>
        currentEvents.filter((event) => event.id !== eventId),
      );
    } catch {
      setActionError(
        "Nie udało się usunąć wydarzenia. Spróbuj ponownie za chwilę.",
      );
    } finally {
      setProcessingEventId(null);
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
              <Link
                className="text-button"
                to={`/organizers/${organizerId}/events/${event.id}/edit`}
                state={{ event, organizer }}
              >
                Edytuj wydarzenie
              </Link>
              <Link
                className="text-button"
                to={`/events/${event.id}/applications`}
                state={{ event, organizer, organizerId }}
              >
                Zarządzaj zgłoszeniami
              </Link>
              {event.status === "draft" && (
                <div className="organizer-event-card__actions">
                  <button
                    className="button button-primary organizer-event-card__publish"
                    type="button"
                    onClick={() => handlePublish(event.id)}
                    disabled={
                      publishingId === event.id ||
                      processingEventId === event.id
                    }
                  >
                    {publishingId === event.id
                      ? "Publikowanie..."
                      : "Opublikuj"}
                  </button>
                  <button
                    className="text-button text-button--danger"
                    type="button"
                    onClick={() => handleDelete(event.id)}
                    disabled={processingEventId === event.id}
                  >
                    {processingEventId === event.id
                      ? "Usuwanie..."
                      : "Usuń szkic"}
                  </button>
                </div>
              )}
              {event.status === "published" && (
                <button
                  className="text-button text-button--danger"
                  type="button"
                  onClick={() => handleCancel(event.id)}
                  disabled={processingEventId === event.id}
                >
                  {processingEventId === event.id
                    ? "Anulowanie..."
                    : "Anuluj wydarzenie"}
                </button>
              )}
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}
