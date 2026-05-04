class OnboardingFlowController {
  OnboardingFlowController({required this.tutorialCompleted, required this.assistedMatches});

  bool tutorialCompleted;
  int assistedMatches;

  bool get rankedUnlocked => tutorialCompleted && assistedMatches >= 3;

  void markTutorialCompleted() {
    tutorialCompleted = true;
  }

  void markAssistedMatchCompleted() {
    if (assistedMatches < 3) {
      assistedMatches += 1;
    }
  }
}
