import { useEffect, useState } from "react";
import { Link, useLocation, useParams } from "react-router-dom";
import {
  acceptEventApplication,
  getEventApplications,
  rejectEventApplication,
} from "../api/events";

const statusLabels = {
  pending: "Oczekuje na decyzję",
  accepted: "Zaakceptowane",
  rejected: "Odrzucone",
  cancelled: "Wycofane",
};

export default function EventApplicationsPage() {
  const { eventId } = useParams();
  const { state } = useLocation();
  const event = state?.event;
  const organizer = state?.organizer;
  const organizerId = state?.organizerId;
  const [applications, setApplications] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [actionError, setActionError] = useState(null);
  const [processingId, setProcessingId] = useState(null);

  useEffect(() => {
    const loadApplications = async () => {
      try {
        const response = await getEventApplications(eventId);
        setApplications(response.data);
      } catch {
        setError("Nie udało się pobrać zgłoszeń. Spróbuj ponownie za chwilę.");
      } finally {
        setIsLoading(false);
      }
    };

    loadApplications();
  }, [eventId]);

  const handleDecision = async (applicationId, decision) => {
    setActionError(null);
    setProcessingId(applicationId);

    try {
      if (decision === "accepted") {
        await acceptEventApplication(applicationId);
      } else {
        await rejectEventApplication(applicationId);
      }

      setApplications((current) =>
        current.map((application) =>
          application.id === applicationId
            ? { ...application, status: decision }
            : application,
        ),
      );
    } catch {
      setActionError(
        "Nie udało się zaktualizować zgłoszenia. Spróbuj ponownie za chwilę.",
      );
    } finally {
      setProcessingId(null);
    }
  };

  return (
    <section className="event-applications-page">
      <Link
        className="back-link"
        to={
          organizerId ? `/organizers/${organizerId}/events` : "/my-organizers"
        }
        state={{ organizer }}
      >
        ← Wydarzenia organizatora
      </Link>

      <p className="eyebrow">Zgłoszenia</p>
      <h1>
        {event ? `Zgłoszenia: ${event.title}` : "Zgłoszenia na wydarzenie"}
      </h1>

      {isLoading && <p>Ładowanie zgłoszeń...</p>}
      {error && (
        <p className="form-message form-message--error" role="alert">
          {error}
        </p>
      )}
      {actionError && (
        <p className="form-message form-message--error" role="alert">
          {actionError}
        </p>
      )}

      {!isLoading && !error && applications.length === 0 && (
        <div className="empty-state">
          <h2>Nie ma jeszcze żadnych zgłoszeń</h2>
          <p>Gdy uczestnicy zapiszą się na wydarzenie, zobaczysz ich tutaj.</p>
        </div>
      )}

      <div className="applications-management-list">
        {applications.map((application) => {
          const isPending = application.status === "pending";
          const isProcessing = processingId === application.id;

          return (
            <article
              className="application-management-card"
              key={application.id}
            >
              <div>
                <h2>{application.name}</h2>
                <p>Id użytkownika: {application.user_id}</p>
              </div>

              <div className="application-management-card__actions">
                <span
                  className={`status-badge status-badge--${application.status}`}
                >
                  {statusLabels[application.status] ?? application.status}
                </span>
                {isPending && (
                  <div className="application-decision-buttons">
                    <button
                      className="button button-primary"
                      type="button"
                      onClick={() => handleDecision(application.id, "accepted")}
                      disabled={isProcessing}
                    >
                      {isProcessing ? "Zapisywanie..." : "Akceptuj"}
                    </button>
                    <button
                      className="text-button text-button--danger"
                      type="button"
                      onClick={() => handleDecision(application.id, "rejected")}
                      disabled={isProcessing}
                    >
                      Odrzuć
                    </button>
                  </div>
                )}
              </div>
            </article>
          );
        })}
      </div>
    </section>
  );
}
