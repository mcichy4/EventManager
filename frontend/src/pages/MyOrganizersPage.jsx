import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { getMyOrganizers } from "../api/organizers";

const roleLabels = {
  owner: "Właściciel",
  member: "Członek",
};

export default function MyOrganizersPage() {
  const [organizers, setOrganizers] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const loadOrganizers = async () => {
      try {
        const response = await getMyOrganizers();
        setOrganizers(response.data);
      } catch {
        setError(
          "Nie udało się pobrać organizatorów. Spróbuj ponownie za chwilę.",
        );
      } finally {
        setIsLoading(false);
      }
    };

    loadOrganizers();
  }, []);

  return (
    <section className="organizers-page">
      <p className="eyebrow">Organizatorzy</p>
      <h1>Moi organizatorzy</h1>
      <p className="organizers-page__intro">
        Organizacje, do których należysz i w których możesz współtworzyć
        wydarzenia.
      </p>

      {isLoading && <p>Ładowanie organizatorów...</p>}
      {error && (
        <p className="form-message form-message--error" role="alert">
          {error}
        </p>
      )}

      {!isLoading && !error && organizers.length === 0 && (
        <div className="empty-state">
          <h2>Nie należysz jeszcze do żadnej organizacji</h2>
          <p>
            Utwórz swoją pierwszą organizację, aby zacząć publikować wydarzenia.
          </p>
          <Link className="button button-primary" to="/organizers/new">
            Utwórz organizację
          </Link>
        </div>
      )}

      <div className="organizers-list">
        {organizers.map((organizer) => (
          <article className="organizer-list-card" key={organizer.id}>
            <div>
              <p className="organizer-list-card__type">{organizer.type}</p>
              <h2>{organizer.name}</h2>
              <p>{organizer.description || "Brak opisu organizacji."}</p>
            </div>
            <dl className="organizer-list-card__meta">
              <div>
                <dt>Twoja rola</dt>
                <dd>{roleLabels[organizer.role] ?? organizer.role}</dd>
              </div>
              <div>
                <dt>Członkowie</dt>
                <dd>{organizer.members_count}</dd>
              </div>
            </dl>
            <Link
              className="text-button"
              to={`/organizers/${organizer.id}/events`}
              state={{ organizer }}
            >
              Zobacz wydarzenia organizatora
            </Link>
          </article>
        ))}
      </div>
    </section>
  );
}
