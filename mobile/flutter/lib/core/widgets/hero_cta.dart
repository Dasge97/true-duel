import 'package:flutter/material.dart';

class HeroCta extends StatelessWidget {
  const HeroCta({super.key, required this.label, required this.onPressed, this.isBusy = false});

  final String label;
  final VoidCallback? onPressed;
  final bool isBusy;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      child: ElevatedButton(
        onPressed: isBusy ? null : onPressed,
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          mainAxisSize: MainAxisSize.min,
          children: [
            if (isBusy)
              const SizedBox(
                width: 18,
                height: 18,
                child: CircularProgressIndicator(strokeWidth: 2),
              ),
            if (isBusy) const SizedBox(width: 8),
            Text(label),
          ],
        ),
      ),
    );
  }
}
