import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { getEvent } from "../api/events";

const categoryLabels = {
  workshops: "Warsztaty",
  sport: "Sport",
  music: "Muzyka",
  networking: "Networking",
  education: "Edukacja",
  other: "Inne",
};

export default function EventDetailPage() {
  const { eventId } = useParams();
  const [event, setEvent] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const loadEvent = async () => {
      setIsLoading(true);
      setError(null);

      try {
        const response = await getEvent(eventId);
        setEvent(response);
      } catch (requestError) {
        setError(
          requestError.response?.status === 404
            ? "To wydarzenie nie jest już dostępne."
            : "Nie udało się pobrać wydarzenia. Spróbuj ponownie za chwilę.",
        );
      } finally {
        setIsLoading(false);
      }
    };

    loadEvent();
  }, [eventId]);

  if (isLoading) {
    return <p>Ładowanie wydarzenia...</p>;
  }

  if (error || !event) {
    return (
      <>
        <Link className="back-link" to="/">
          ← Wszystkie wydarzenia
        </Link>
        <p role="alert">{error}</p>
      </>
    );
  }

  const date = new Intl.DateTimeFormat("pl-PL", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  }).format(new Date(event.starts_at));

  const formatTime = (value) =>
    new Intl.DateTimeFormat("pl-PL", {
      hour: "2-digit",
      minute: "2-digit",
    }).format(new Date(value));

  const category = categoryLabels[event.category] ?? "Inne";
  const acceptedApplicationsCount = event.accepted_applications_count ?? 0;
  const availablePlaces =
    event.participant_limit === null
      ? null
      : event.participant_limit - acceptedApplicationsCount;

  return (
    <>
      <Link className="back-link" to="/">
        ← Wszystkie wydarzenia
      </Link>

      <section className="event-detail-hero">
        <div>
          <p className="eyebrow">{category}</p>
          <h1>{event.title}</h1>
          <p className="event-detail-hero__organizer">
            Organizuje {event.organizer.name}
          </p>
        </div>

        <div className="event-detail-visual">
          <span>{category}</span>
        </div>
      </section>

      <div className="event-detail-layout">
        <article className="event-detail-content">
          <section>
            <h2>O wydarzeniu</h2>
            <p>{event.description}</p>
          </section>

          <section>
            <h2>Organizator</h2>
            <div className="organizer-card">
              <div className="organizer-card__avatar">PF</div>
              <div>
                <strong>{event.organizer.name}</strong>
                <p>Tworzymy przestrzeń do kreatywnego działania.</p>
              </div>
            </div>
          </section>
        </article>

        <aside className="event-registration-card">
          <div className="event-meta">
            <div>
              <span className="event-meta__label">Data</span>
              <strong>{date}</strong>
            </div>

            <div>
              <span className="event-meta__label">Godzina</span>
              <strong>
                {formatTime(event.starts_at)}–{formatTime(event.ends_at)}
              </strong>
            </div>

            <div>
              <span className="event-meta__label">Miejsce</span>
              <strong>{event.location}</strong>
              <small>{event.address}</small>
            </div>
          </div>

          <div className="registration-summary">
            {event.participant_limit === null ? (
              <strong>Brak limitu miejsc</strong>
            ) : (
              <>
                <strong>Zostały {availablePlaces} miejsca</strong>
                <span>
                  {acceptedApplicationsCount} z {event.participant_limit} osób
                  zgłoszonych
                </span>
              </>
            )}

            {event.participant_limit !== null && (
              <div className="capacity-bar">
                <span
                  style={{
                    width: `${(acceptedApplicationsCount / event.participant_limit) * 100}%`,
                  }}
                />
              </div>
            )}
          </div>

          <button
            className="button button-primary registration-button"
            type="button"
          >
            Zgłoś się na wydarzenie
          </button>

          <p className="registration-note">
            Aby się zgłosić, zaloguj się lub załóż konto.
          </p>
        </aside>
      </div>
    </>
  );
}
