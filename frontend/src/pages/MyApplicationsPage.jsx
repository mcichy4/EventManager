import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { cancelApplication, getMyApplications } from "../api/events";

const statusLabels = {
  pending: "Oczekuje na decyzję",
  accepted: "Zaakceptowane",
  rejected: "Odrzucone",
  cancelled: "Wycofane",
};

export default function MyApplicationsPage() {
  const [applications, setApplications] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [cancellingId, setCancellingId] = useState(null);

  useEffect(() => {
    const loadApplications = async () => {
      try {
        setApplications(await getMyApplications());
      } catch {
        setError(
          "Nie udało się pobrać Twoich zgłoszeń. Spróbuj ponownie za chwilę.",
        );
      } finally {
        setIsLoading(false);
      }
    };

    loadApplications();
  }, []);

  const handleCancel = async (applicationId) => {
    setCancellingId(applicationId);
    setError(null);

    try {
      await cancelApplication(applicationId);
      setApplications((current) =>
        current.map((application) =>
          application.id === applicationId
            ? { ...application, status: "cancelled" }
            : application,
        ),
      );
    } catch {
      setError("Nie udało się wycofać zgłoszenia. Spróbuj ponownie za chwilę.");
    } finally {
      setCancellingId(null);
    }
  };

  return (
    <section className="applications-page">
      <p className="eyebrow">Twoje konto</p>
      <h1>Moje zgłoszenia</h1>
      <p className="applications-page__intro">
        Tutaj sprawdzisz status swoich zapisów na wydarzenia.
      </p>

      {isLoading && <p>Ładowanie zgłoszeń...</p>}
      {error && (
        <p className="form-message form-message--error" role="alert">
          {error}
        </p>
      )}
      {!isLoading && !error && applications.length === 0 && (
        <div className="empty-state">
          <h2>Nie masz jeszcze żadnych zgłoszeń</h2>
          <p>Przejrzyj dostępne wydarzenia i znajdź coś dla siebie.</p>
          <Link className="button button-primary" to="/">
            Odkryj wydarzenia
          </Link>
        </div>
      )}

      <div className="applications-list">
        {applications.map((application) => {
          const event = application.event;
          const canCancel = application.status === "pending";

          return (
            <article className="application-card" key={application.id}>
              <div>
                <p className="application-card__date">
                  {new Intl.DateTimeFormat("pl-PL", {
                    day: "numeric",
                    month: "long",
                    year: "numeric",
                  }).format(new Date(event.starts_at))}
                </p>
                <h2>{event.title}</h2>
                <p>{event.location}</p>
                <p className="application-card__organizer">
                  Organizuje {event.organizer.name}
                </p>
              </div>
              <div className="application-card__actions">
                <span
                  className={`status-badge status-badge--${application.status}`}
                >
                  {statusLabels[application.status] ?? application.status}
                </span>
                <Link className="text-button" to={`/events/${event.id}`}>
                  Zobacz wydarzenie
                </Link>
                {canCancel && (
                  <button
                    className="text-button text-button--danger"
                    type="button"
                    onClick={() => handleCancel(application.id)}
                    disabled={cancellingId === application.id}
                  >
                    {cancellingId === application.id
                      ? "Wycofywanie..."
                      : "Wycofaj zgłoszenie"}
                  </button>
                )}
              </div>
            </article>
          );
        })}
      </div>
    </section>
  );
}
