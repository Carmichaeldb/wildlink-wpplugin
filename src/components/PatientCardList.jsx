import React, { useState, useEffect } from "react";
import PatientCard from "./PatientCard";
import PatientModal from "./PatientModal";

const PatientCardList = ({ initialPatientId }) => {
  const [patients, setPatients] = useState([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [selectedPatient, setSelectedPatient] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    loadPatients();
  }, [currentPage]);

  // load modal if patient url param is present
  useEffect(() => {
    if (!initialPatientId || loading || patients.length === 0) return;
    
    const sharedPatient = patients.find(p => 
      p.patient_id === initialPatientId 
    );
    
    if (sharedPatient) {
      setSelectedPatient(sharedPatient);
    } else {
      console.log("Patient not found:", initialPatientId);
      console.log("Available patients:", patients.map(p => ({id: p.id, patient_id: p.patient_id})));
    }
  }, [initialPatientId, patients, loading]);

  // load patients
  const loadPatients = async () => {
    try {
      const response = await wp.apiFetch({
        path: `/wildlink/v1/patients?paginate=1&page=${currentPage}`,
        method: "GET",
      });

      if (response.data) {
        setPatients(response.data);
        setTotalPages(response.total_pages);
      } else {
        setPatients(response);
        setTotalPages(1);
      }
      setLoading(false);
    } catch (err) {
      console.error("Error loading patients:", err);
      setError("Failed to load patients");
      setLoading(false);
    }
  };

  // change page
  const handlePageChange = (newPage) => {
    setCurrentPage(newPage);
    window.scrollTo(0, 0);
  };

  // view story
  const handleViewStory = (patient) => {
    setSelectedPatient(patient);
  };

  // loading
  if (loading) return <div className="loading">Loading patients...</div>;
  // error
  if (error) return <div className="error">{error}</div>;
  // no patients
  if (!Array.isArray(patients) || patients.length === 0)
    return <div>No patients found.</div>;

  return (
    <div className="patient-cards-container">
      {/* patient cards grid */}
      <div className="patient-cards-grid">
        {patients.map((patient) => (
          <PatientCard
            key={patient.id}
            patient={patient}
            onViewStory={handleViewStory}
          />
        ))}
      </div>
      {/* pagination */}
      {totalPages > 1 && (
        <div className="pagination">
          {currentPage > 1 && (
            <button
              onClick={() => handlePageChange(currentPage - 1)}
              className="button prev-page"
              aria-label="Previous page"
            >
              ← Previous
            </button>
          )}
          <span className="page-info">
            Page {currentPage} of {totalPages}
          </span>
          {currentPage < totalPages && (
            <button
              onClick={() => handlePageChange(currentPage + 1)}
              className="button next-page"
              aria-label="Next page"
            >
              Next →
            </button>
          )}
        </div>
      )}
      {/* patient modal */}
      {selectedPatient && (
        <PatientModal
          patient={selectedPatient}
          onClose={() => setSelectedPatient(null)}
        />
      )}
    </div>
  );
};

export default PatientCardList;
