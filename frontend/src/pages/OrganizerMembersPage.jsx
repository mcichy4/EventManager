import { useCallback, useEffect, useState } from "react";
import { Link, useLocation, useParams } from "react-router-dom";
import {
  addOrganizerMember,
  getOrganizerMembers,
  removeOrganizerMember,
} from "../api/organizers";

const roleLabels = {
  owner: "Właściciel",
  member: "Członek",
};

export default function OrganizerMembersPage() {
  const { organizerId } = useParams();
  const { state } = useLocation();
  const organizer = state?.organizer;
  const [members, setMembers] = useState([]);
  const [email, setEmail] = useState("");
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [removingMemberId, setRemovingMemberId] = useState(null);
  const [error, setError] = useState(null);
  const [successMessage, setSuccessMessage] = useState(null);

  const loadMembers = useCallback(async () => {
    const response = await getOrganizerMembers(organizerId);
    setMembers(response);
  }, [organizerId]);

  useEffect(() => {
    const loadPage = async () => {
      try {
        await loadMembers();
      } catch {
        setError("Nie udało się pobrać członków organizacji.");
      } finally {
        setIsLoading(false);
      }
    };

    loadPage();
  }, [loadMembers]);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setError(null);
    setSuccessMessage(null);
    setIsSubmitting(true);

    try {
      await addOrganizerMember(organizerId, email);
      await loadMembers();
      setEmail("");
      setSuccessMessage("Członek został dodany do organizacji.");
    } catch {
      setError("Nie udało się dodać użytkownika. Sprawdź adres e-mail.");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleRemove = async (member) => {
    if (!window.confirm(`Usunąć ${member.name} z organizacji?`)) {
      return;
    }

    setError(null);
    setSuccessMessage(null);
    setRemovingMemberId(member.id);

    try {
      await removeOrganizerMember(organizerId, member.id);
      setMembers((currentMembers) =>
        currentMembers.filter(
          (currentMember) => currentMember.id !== member.id,
        ),
      );
      setSuccessMessage("Członek został usunięty z organizacji.");
    } catch {
      setError("Nie udało się usunąć członka organizacji.");
    } finally {
      setRemovingMemberId(null);
    }
  };

  return (
    <section className="organizer-members-page">
      <Link
        className="back-link"
        to={`/organizers/${organizerId}/events`}
        state={{ organizer }}
      >
        &larr; Powrót do wydarzeń organizatora
      </Link>
      <p className="eyebrow">Organizator</p>
      <h1>
        {organizer ? `Członkowie: ${organizer.name}` : "Członkowie organizacji"}
      </h1>
      <p className="organizer-members-page__intro">
        Dodaj użytkownika, który ma już konto w EventManagerze.
      </p>

      <form className="member-form" onSubmit={handleSubmit}>
        <label htmlFor="member-email">Adres e-mail użytkownika</label>
        <div className="member-form__controls">
          <input
            id="member-email"
            type="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            placeholder="uzytkownik@example.com"
            required
          />
          <button
            className="button button-primary"
            type="submit"
            disabled={isSubmitting}
          >
            {isSubmitting ? "Dodawanie..." : "Dodaj członka"}
          </button>
        </div>
      </form>

      {error && (
        <p className="form-message form-message--error" role="alert">
          {error}
        </p>
      )}
      {successMessage && (
        <p className="form-message form-message--success">{successMessage}</p>
      )}
      {isLoading && <p>Ładowanie członków...</p>}

      {!isLoading && !error && (
        <div className="members-list">
          {members.map((member) => (
            <article className="member-card" key={member.id}>
              <div>
                <h2>{member.name}</h2>
                <p>{member.email}</p>
              </div>
              <div className="member-card__actions">
                <span className={`member-role member-role--${member.role}`}>
                  {roleLabels[member.role] ?? member.role}
                </span>
                {member.role !== "owner" && (
                  <button
                    className="text-button text-button--danger"
                    type="button"
                    onClick={() => handleRemove(member)}
                    disabled={removingMemberId === member.id}
                  >
                    {removingMemberId === member.id ? "Usuwanie..." : "Usuń"}
                  </button>
                )}
              </div>
            </article>
          ))}
        </div>
      )}
    </section>
  );
}
