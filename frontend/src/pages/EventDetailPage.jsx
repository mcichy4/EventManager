import { useEffect, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { useAuth } from "../auth/useAuth";
import { applyToEvent, getEvent } from "../api/events";

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
  const navigate = useNavigate();
  const { isAuthenticated } = useAuth();
  const [event, setEvent] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [applicationMessage, setApplicationMessage] = useState(null);
  const [isApplying, setIsApplying] = useState(false);

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
      : Math.max(0, event.participant_limit - acceptedApplicationsCount);
  const hasReachedCapacity = availablePlaces === 0;
  const hasApplied = applicationMessage?.type === "success";
  const deadline = event.application_deadline
    ? new Intl.DateTimeFormat("pl-PL", {
        day: "numeric",
        month: "long",
        year: "numeric",
      }).format(new Date(event.application_deadline))
    : null;

  const handleApplication = async () => {
    if (!isAuthenticated) {
      navigate("/login", {
        state: { from: { pathname: `/events/${eventId}` } },
      });
      return;
    }

    setIsApplying(true);
    setApplicationMessage(null);

    try {
      await applyToEvent(eventId);
      setApplicationMessage({
        type: "success",
        text: "Twoje zgłoszenie zostało wysłane.",
      });
    } catch (requestError) {
      const message = requestError.response?.data?.message;
      setApplicationMessage({
        type: "error",
        text:
          message === "User has already applied to this event."
            ? "Masz już zgłoszenie na to wydarzenie."
            : message === "Application deadline has passed for this event."
              ? "Termin zgłoszeń na to wydarzenie minął."
              : "Nie udało się wysłać zgłoszenia. Spróbuj ponownie za chwilę.",
      });
    } finally {
      setIsApplying(false);
    }
  };

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

            {deadline && (
              <div>
                <span className="event-meta__label">Zgłoszenia do</span>
                <strong>{deadline}</strong>
              </div>
            )}
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
                    width: `${Math.min(100, (acceptedApplicationsCount / event.participant_limit) * 100)}%`,
                  }}
                />
              </div>
            )}
          </div>

          <button
            className="button button-primary registration-button"
            type="button"
            onClick={handleApplication}
            disabled={isApplying || hasReachedCapacity || hasApplied}
          >
            {isApplying
              ? "Wysyłanie zgłoszenia..."
              : hasApplied
                ? "Zgłoszenie wysłane"
                : hasReachedCapacity
                  ? "Brak wolnych miejsc"
                  : "Zgłoś się na wydarzenie"}
          </button>

          {applicationMessage && (
            <p
              className={`form-message form-message--${applicationMessage.type}`}
              role="status"
            >
              {applicationMessage.text}
            </p>
          )}

          {!isAuthenticated && (
            <p className="registration-note">
              Aby się zgłosić, zaloguj się lub załóż konto.
            </p>
          )}
        </aside>
      </div>
    </>
  );
}
